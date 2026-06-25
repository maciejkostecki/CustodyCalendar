import { useState, useEffect } from 'react'
import { getIncomingSwapRequests } from '../api/swaps'
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

function PendingRequests() {
  const [requests, setRequests] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    getIncomingSwapRequests()
      .then(setRequests)
      .catch(() => setError('Could not load swap requests.'))
  }, [])

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
            <li key={req.id} className="pending-item" style={{ '--parent-color': req.from_color }}>
              <div className="pending-item-head">
                <span className="pending-date">{formatDate(req)}</span>
                <span className="pending-parent">{req.from_label}</span>
              </div>
              {req.comment && <p className="pending-comment">“{req.comment}”</p>}
              <p className="pending-meta">
                Requested by {req.requested_by_label} · {formatSubmitted(req.created_at)}
              </p>
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}

export default PendingRequests
