import { useState, useEffect } from 'react'
import { getIncomingSwapRequests, approveSwapRequest, rejectSwapRequest } from '../api/swaps'
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

function RequestItem({ req, onDecided }) {
  const [action, setAction] = useState(null) // null | 'approve' | 'reject'
  const [comment, setComment] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState(null)

  const confirm = async () => {
    setSubmitting(true)
    setError(null)
    try {
      if (action === 'approve') {
        await approveSwapRequest(req.id, comment)
      } else {
        await rejectSwapRequest(req.id, comment)
      }
      onDecided()
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

      {action ? (
        <div className="pending-decide">
          <textarea
            value={comment}
            onChange={(e) => setComment(e.target.value)}
            rows={2}
            maxLength={1000}
            placeholder="Optional comment…"
          />
          {error && <p className="pending-error" role="alert">{error}</p>}
          <div className="pending-actions">
            <button type="button" onClick={() => setAction(null)} disabled={submitting}>
              Cancel
            </button>
            <button
              type="button"
              className={action === 'approve' ? 'primary' : 'danger'}
              onClick={confirm}
              disabled={submitting}
            >
              {submitting
                ? action === 'approve' ? 'Approving…' : 'Rejecting…'
                : action === 'approve' ? 'Confirm approval' : 'Confirm rejection'}
            </button>
          </div>
        </div>
      ) : (
        <div className="pending-actions">
          <button type="button" className="danger" onClick={() => setAction('reject')}>
            Reject
          </button>
          <button type="button" className="primary" onClick={() => setAction('approve')}>
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

  const handleDecided = async () => {
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
            <RequestItem key={req.id} req={req} onDecided={handleDecided} />
          ))}
        </ul>
      )}
    </section>
  )
}

export default PendingRequests
