import { useState, useEffect, useCallback } from 'react'
import { getCalendar } from '../api/calendar'
import SwapProposalModal from './SwapProposalModal'
import './Calendar.css'

const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

function parts(dateStr) {
  const [year, month, day] = dateStr.split('-').map(Number)
  return { year, month, day }
}

function rangeTitle(days) {
  const a = parts(days[0].date)
  const b = parts(days[days.length - 1].date)
  const left = `${a.day} ${MONTHS[a.month - 1]}`
  const right = `${b.day} ${MONTHS[b.month - 1]}`
  const year = a.year === b.year ? `${b.year}` : `${a.year}–${b.year}`
  return `${left} – ${right} ${year}`
}

function chunkWeeks(days) {
  const weeks = []
  for (let i = 0; i < days.length; i += 7) {
    weeks.push(days.slice(i, i + 7))
  }
  return weeks
}

function DayCell({ day, onSelect }) {
  const isFuture = !day.isPast && !day.isToday

  const classes = ['day-cell']
  if (day.isToday) classes.push('today')
  if (day.isPast) classes.push('past')
  if (day.pending) classes.push('pending')
  if (isFuture) classes.push('selectable')

  const { day: dayNumber, month } = parts(day.date)
  // Show the month label on the first cell of each month for context.
  const showMonth = dayNumber === 1

  const interactiveProps = isFuture
    ? {
        role: 'button',
        tabIndex: 0,
        onClick: () => onSelect(day),
        onKeyDown: (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault()
            onSelect(day)
          }
        },
      }
    : {}

  return (
    <div
      className={classes.join(' ')}
      style={{ '--parent-color': day.color }}
      aria-current={day.isToday ? 'date' : undefined}
      {...interactiveProps}
    >
      <div className="day-head">
        <span className="day-number">
          {dayNumber}
          {showMonth && <span className="day-month"> {MONTHS[month - 1]}</span>}
        </span>
        {day.pending && (
          <>
            <span className="day-pending" title="Swap pending">Pending</span>
            <span className="day-pending-dot" title="Swap pending" aria-label="Swap pending" />
          </>
        )}
      </div>
      <span className="day-parent">{day.label}</span>
      <span className="day-parent-short" aria-hidden="true">{day.label[0]}</span>
    </div>
  )
}

function Calendar() {
  const [days, setDays] = useState(null)
  const [error, setError] = useState(null)
  const [selectedDay, setSelectedDay] = useState(null)

  const load = useCallback(() => {
    return getCalendar()
      .then((data) => setDays(data.days))
      .catch(() => setError('Could not load the calendar. Please try again.'))
  }, [])

  useEffect(() => {
    load()
  }, [load])

  const handleSubmitted = async () => {
    setSelectedDay(null)
    await load()
  }

  if (error) return <p role="alert">{error}</p>
  if (!days) return <p>Loading calendar…</p>

  const weeks = chunkWeeks(days)

  return (
    <div className="calendar">
      <h2 className="calendar-range">{rangeTitle(days)}</h2>
      <div className="calendar-weekdays">
        {WEEKDAYS.map((wd) => (
          <span key={wd}>{wd}</span>
        ))}
      </div>
      {weeks.map((week) => (
        <div className="calendar-week" key={week[0].date}>
          {week.map((day) => (
            <DayCell key={day.date} day={day} onSelect={setSelectedDay} />
          ))}
        </div>
      ))}

      {selectedDay && (
        <SwapProposalModal
          day={selectedDay}
          onClose={() => setSelectedDay(null)}
          onSubmitted={handleSubmitted}
        />
      )}
    </div>
  )
}

export default Calendar
