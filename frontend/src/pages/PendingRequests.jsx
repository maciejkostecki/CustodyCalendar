import { useState, useEffect } from 'react'
import { getIncomingSwapRequests, approveSwapRequest } from '../api/swaps'
import './PendingRequests.css'

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

function formatDate(req) {
  const [, month, day] = req.date.split('-').map(Number)
  return `${req.weekday}, ${day} ${MONTHS[month - 1]}`
}

function formatSubmitted(iso) {
  const d = new Date(iso)
  return d.toLocaleString(undefined, {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function RequestItem({ req, onApproved }) {
  const [open, setOpen] = useState(false)
  const [comment, setComment] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState(null)

  const approve = async () => {
    setSubmitting(true)
    setError(null)
    try {
      await approveSwapRequest(req.id, comment)
      onApproved()
    } catch (err) {
      setError(err.message)
      setSubmitting(false)
    }
  }

  return (
    <li className="pending-item" style={{ '--parent-color': req.from_color }}>
      <div className="pending-item-head">
        <span className="pending-date">{formatDate(req)}</span>
        <span className="pending-parent">{req.from_label}</span>
      </div>
      {req.comment && <p className="pending-comment">“{req.comment}”</p>}
      <p className="pending-meta">
        Requested by {req.requested_by_label} · {formatSubmitted(req.created_at)}
      </p>

      {open ? (
        <div className="pending-approve">
          <textarea
            value={comment}
            onChange={(e) => setComment(e.target.value)}
            rows={2}
            maxLength={1000}
            placeholder="Optional comment…"
          />
          {error && <p className="pending-error" role="alert">{error}</p>}
          <div className="pending-actions">
            <button type="button" onClick={() => setOpen(false)} disabled={submitting}>
              Cancel
            </button>
            <button type="button" className="primary" onClick={approve} disabled={submitting}>
              {submitting ? 'Approving…' : 'Confirm approval'}
            </button>
          </div>
        </div>
      ) : (
        <div className="pending-actions">
          <button type="button" className="primary" onClick={() => setOpen(true)}>
            Approve
          </button>
        </div>
      )}
    </li>
  )
}

function PendingRequests({ onDecision }) {
  const [requests, setRequests] = useState(null)
  const [error, setError] = useState(null)

  const load = () =>
    getIncomingSwapRequests()
      .then(setRequests)
      .catch(() => setError('Could not load swap requests.'))

  useEffect(() => {
    load()
  }, [])

  const handleApproved = async () => {
    await load()
    if (onDecision) onDecision()
  }

  if (error) return <p role="alert">{error}</p>
  if (!requests) return null

  const count = requests.length

  return (
    <section className="pending-requests" aria-label="Incoming swap requests">
      <header className="pending-header">
        <h2>Swap requests</h2>
        {count > 0 && <span className="pending-badge">{count}</span>}
      </header>

      {count === 0 ? (
        <p className="pending-empty">No swap requests awaiting your decision.</p>
      ) : (
        <ul className="pending-list">
          {requests.map((req) => (
            <RequestItem key={req.id} req={req} onApproved={handleApproved} />
          ))}
        </ul>
      )}
    </section>
  )
}

export default PendingRequests
