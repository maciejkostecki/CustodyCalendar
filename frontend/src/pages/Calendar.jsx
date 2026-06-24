import { useState, useEffect } from 'react'
import { getCalendar } from '../api/calendar'
import './Calendar.css'

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

  const dayNumber = Number(day.date.slice(8, 10))

  return (
    <div
      className={classes.join(' ')}
      style={{ '--parent-color': day.color }}
      aria-current={day.isToday ? 'date' : undefined}
    >
      <div className="day-head">
        <span className="day-weekday">{day.weekday}</span>
        <span className="day-number">{dayNumber}</span>
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
