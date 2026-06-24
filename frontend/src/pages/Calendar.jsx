import { useState, useEffect } from 'react'
import { getCalendar } from '../api/calendar'
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

function DayCell({ day }) {
  const classes = ['day-cell']
  if (day.isToday) classes.push('today')
  if (day.isPast) classes.push('past')

  const { day: dayNumber, month } = parts(day.date)
  // Show the month label on the first cell of each month for context.
  const showMonth = dayNumber === 1

  return (
    <div
      className={classes.join(' ')}
      style={{ '--parent-color': day.color }}
      aria-current={day.isToday ? 'date' : undefined}
    >
      <div className="day-head">
        <span className="day-number">
          {dayNumber}
          {showMonth && <span className="day-month"> {MONTHS[month - 1]}</span>}
        </span>
      </div>
      <span className="day-parent">{day.label}</span>
    </div>
  )
}

function Calendar() {
  const [days, setDays] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    getCalendar()
      .then((data) => setDays(data.days))
      .catch(() => setError('Could not load the calendar. Please try again.'))
  }, [])

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
            <DayCell key={day.date} day={day} />
          ))}
        </div>
      ))}
    </div>
  )
}

export default Calendar
