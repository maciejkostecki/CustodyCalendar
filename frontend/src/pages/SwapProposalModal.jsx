import { useState } from 'react'
import { createSwapRequest } from '../api/swaps'
import './SwapProposalModal.css'

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

function formatDate(day) {
  const [, month, dayNum] = day.date.split('-').map(Number)
  return `${day.weekday}, ${dayNum} ${MONTHS[month - 1]}`
}

function SwapProposalModal({ day, onClose, onSubmitted }) {
  const [comment, setComment] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState(null)

  const alreadyPending = day.pending

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSubmitting(true)
    setError(null)
    try {
      await createSwapRequest({ date: day.date, comment })
      onSubmitted()
    } catch (err) {
      setError(err.message)
      setSubmitting(false)
    }
  }

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div
        className="modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="swap-modal-title"
        onClick={(e) => e.stopPropagation()}
      >
        <h2 id="swap-modal-title">Propose a swap</h2>

        <dl className="modal-details">
          <div>
            <dt>Date</dt>
            <dd>{formatDate(day)}</dd>
          </div>
          <div>
            <dt>Current custodial parent</dt>
            <dd style={{ color: day.color }}>{day.label}</dd>
          </div>
        </dl>

        {alreadyPending ? (
          <p className="modal-notice" role="alert">
            A swap request is already pending for this day.
          </p>
        ) : (
          <form onSubmit={handleSubmit}>
            <label className="modal-field">
              <span>Comment (optional)</span>
              <textarea
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                rows={3}
                maxLength={1000}
                placeholder="Add context for the other parent…"
              />
            </label>

            {error && <p className="modal-error" role="alert">{error}</p>}

            <div className="modal-actions">
              <button type="button" onClick={onClose} disabled={submitting}>
                Cancel
              </button>
              <button type="submit" className="primary" disabled={submitting}>
                {submitting ? 'Submitting…' : 'Propose swap'}
              </button>
            </div>
          </form>
        )}

        {alreadyPending && (
          <div className="modal-actions">
            <button type="button" onClick={onClose}>Close</button>
          </div>
        )}
      </div>
    </div>
  )
}

export default SwapProposalModal
