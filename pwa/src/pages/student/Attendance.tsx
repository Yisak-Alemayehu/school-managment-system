import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import Layout from '../../components/Layout'
import StatCard from '../../components/StatCard'
import LoadingSpinner from '../../components/LoadingSpinner'
import { apiStudentAttendance, AttendanceRecord } from '../../api/client'

const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

function statusColor(status?: string): string {
  switch (status) {
    case 'present': return 'bg-green-500'
    case 'absent':  return 'bg-red-500'
    case 'late':    return 'bg-yellow-400'
    case 'excused': return 'bg-blue-400'
    default:        return 'bg-gray-200'
  }
}

function statusLabel(status: string): string {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

interface CalendarMonthProps {
  year: number
  month: number  // 1-indexed
  records: AttendanceRecord[]
}

function CalendarMonth({ year, month, records }: CalendarMonthProps) {
  const firstDay = new Date(year, month - 1, 1).getDay()
  const daysInMonth = new Date(year, month, 0).getDate()

  const byDate: Record<string, AttendanceRecord> = {}
  records.forEach(r => { byDate[r.date] = r })

  const cells: (null | number)[] = [...Array(firstDay).fill(null), ...Array.from({ length: daysInMonth }, (_, i) => i + 1)]
  // pad to complete row
  while (cells.length % 7 !== 0) cells.push(null)

  return (
    <div>
      <p className="text-center text-sm font-semibold text-gray-700 mb-2">
        {MONTHS[month - 1]} {year}
      </p>
      <div className="grid grid-cols-7 gap-0.5 mb-1">
        {DAYS.map(d => <p key={d} className="text-center text-[10px] font-medium text-gray-400 py-1">{d}</p>)}
      </div>
      <div className="grid grid-cols-7 gap-0.5">
        {cells.map((day, idx) => {
          if (!day) return <div key={idx} />
          const dateStr = `${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`
          const rec = byDate[dateStr]
          return (
            <div
              key={idx}
              title={rec ? `${dateStr}: ${statusLabel(rec.status)}` : dateStr}
              className={`aspect-square rounded-md flex items-center justify-center text-[11px] font-medium
                ${rec ? statusColor(rec.status) + ' text-white' : 'text-gray-600'}`}
            >
              {day}
            </div>
          )
        })}
      </div>
    </div>
  )
}

export default function StudentAttendance() {
  const now = new Date()
  const [year, setYear] = useState(now.getFullYear())
  const [month, setMonth] = useState(now.getMonth() + 1)

  const { data, isLoading } = useQuery({
    queryKey: ['student-attendance', year, month],
    queryFn: () => apiStudentAttendance(year, month),
  })

  function prev() {
    if (month === 1) { setYear(y => y - 1); setMonth(12) }
    else setMonth(m => m - 1)
  }
  function next() {
    if (month === 12) { setYear(y => y + 1); setMonth(1) }
    else setMonth(m => m + 1)
  }

  const summary = data?.summary
  const records: AttendanceRecord[] = data?.records ?? []

  return (
    <Layout title="Attendance" showBack>
      <div className="space-y-5">
        {/* Month nav */}
        <div className="flex items-center justify-between">
          <button onClick={prev} className="w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center shadow-sm">
            <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" /></svg>
          </button>
          <span className="font-semibold text-gray-800">{MONTHS[month-1]} {year}</span>
          <button onClick={next} className="w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center shadow-sm">
            <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" /></svg>
          </button>
        </div>

        {/* Summary stats */}
        {summary && (
          <div className="grid grid-cols-2 gap-3">
            <StatCard title="Attendance" value={`${summary.percentage}%`} subtitle={`${summary.present}/${summary.total} days`} icon="✅" accent={summary.percentage >= 90 ? 'green' : summary.percentage >= 75 ? 'orange' : 'red'} />
            <StatCard title="Absent" value={summary.absent} subtitle={`Late: ${summary.late ?? 0}`} icon="❌" accent="red" />
          </div>
        )}

        {isLoading && <LoadingSpinner />}

        {/* Calendar */}
        {!isLoading && (
          <div className="card">
            <CalendarMonth year={year} month={month} records={records} />
          </div>
        )}

        {/* Legend */}
        <div className="flex flex-wrap gap-3 text-xs text-gray-600">
          {[['Present','bg-green-500'],['Absent','bg-red-500'],['Late','bg-yellow-400'],['Excused','bg-blue-400']].map(([label, cls]) => (
            <span key={label} className="flex items-center gap-1.5">
              <span className={`w-3 h-3 rounded-sm ${cls}`} />
              {label}
            </span>
          ))}
        </div>

        {/* Record list */}
        {records.length > 0 && (
          <section>
            <h3 className="section-title">Daily Records</h3>
            <div className="card divide-y divide-gray-100 p-0 overflow-hidden">
              {records.map(r => (
                <div key={r.date} className="flex items-center justify-between px-4 py-3">
                  <span className="text-sm text-gray-700">{r.date}</span>
                  <span className={`badge ${r.status === 'present' ? 'badge-green' : r.status === 'absent' ? 'badge-red' : r.status === 'late' ? 'badge-yellow' : 'badge-blue'}`}>
                    {statusLabel(r.status)}
                  </span>
                </div>
              ))}
            </div>
          </section>
        )}
      </div>
    </Layout>
  )
}
