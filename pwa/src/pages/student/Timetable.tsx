import { useQuery } from '@tanstack/react-query'
import Layout from '../../components/Layout'
import LoadingSpinner from '../../components/LoadingSpinner'
import { apiStudentTimetable, TimetableSlot } from '../../api/client'

const DAYS_ORDER = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']
const DAY_LABEL: Record<string, string> = {
  monday: 'Monday', tuesday: 'Tuesday', wednesday: 'Wednesday',
  thursday: 'Thursday', friday: 'Friday', saturday: 'Saturday', sunday: 'Sunday',
}

const PERIOD_COLORS = [
  'bg-blue-50 border-blue-200',
  'bg-green-50 border-green-200',
  'bg-purple-50 border-purple-200',
  'bg-orange-50 border-orange-200',
  'bg-pink-50 border-pink-200',
  'bg-teal-50 border-teal-200',
]

export default function StudentTimetable() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['student-timetable'],
    queryFn: apiStudentTimetable,
  })

  const grouped: Record<string, TimetableSlot[]> = data?.timetable ?? {}

  // today's day name
  const todayKey = DAYS_ORDER[new Date().getDay() === 0 ? 6 : new Date().getDay() - 1]

  return (
    <Layout title="Timetable" showBack>
      <div className="space-y-5">
        {isLoading && <LoadingSpinner />}
        {error && <div className="card bg-red-50 border border-red-200 text-red-700 text-sm">Failed to load timetable.</div>}

        {!isLoading && Object.keys(grouped).length === 0 && (
          <div className="card text-center py-10 text-gray-400">
            <p className="text-3xl mb-2">🗓️</p>
            <p className="text-sm">No timetable available yet.</p>
          </div>
        )}

        {DAYS_ORDER.filter(day => grouped[day]?.length).map(day => (
          <section key={day}>
            <div className="flex items-center gap-2 mb-2">
              <h3 className="section-title mb-0">{DAY_LABEL[day]}</h3>
              {day === todayKey && (
                <span className="badge badge-green text-[10px]">Today</span>
              )}
            </div>
            <div className="space-y-2">
              {grouped[day].map((slot, i) => (
                <div
                  key={i}
                  className={`card flex items-center gap-3 border ${PERIOD_COLORS[i % PERIOD_COLORS.length]}`}
                >
                  {/* Time column */}
                  <div className="text-center flex-shrink-0 w-16">
                    <p className="text-xs font-semibold text-gray-700">{slot.start_time?.slice(0,5)}</p>
                    <p className="text-[10px] text-gray-400">{slot.end_time?.slice(0,5)}</p>
                  </div>
                  {/* Separator */}
                  <div className="w-0.5 h-10 bg-gray-200 rounded flex-shrink-0" />
                  {/* Subject */}
                  <div className="min-w-0">
                    <p className="text-sm font-semibold text-gray-900 truncate">{slot.subject_name}</p>
                    {slot.teacher_name && (
                      <p className="text-xs text-gray-500 truncate">{slot.teacher_name}</p>
                    )}
                    {slot.room && (
                      <p className="text-xs text-gray-400">Room {slot.room}</p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </section>
        ))}
      </div>
    </Layout>
  )
}
