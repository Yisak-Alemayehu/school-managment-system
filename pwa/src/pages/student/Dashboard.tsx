import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import Layout from '../../components/Layout'
import StatCard from '../../components/StatCard'
import LoadingSpinner from '../../components/LoadingSpinner'
import { apiStudentDashboard } from '../../api/client'
import { useAuth } from '../../contexts/AuthContext'

export default function StudentDashboard() {
  const { student } = useAuth()

  const { data, isLoading, error } = useQuery({
    queryKey: ['student-dashboard'],
    queryFn: apiStudentDashboard,
  })

  return (
    <Layout title="Dashboard">
      <div className="space-y-5">
        {/* Greeting */}
        <div>
          <p className="text-gray-400 text-sm">Good day,</p>
          <h2 className="text-xl font-bold text-gray-900 truncate">
            {student?.first_name ?? data?.enrollment?.full_name ?? 'Student'}
          </h2>
          {data?.enrollment && (
            <p className="text-sm text-gray-500">
              {data.enrollment.grade_name} {data.enrollment.section_name} &middot; {data.enrollment.academic_year}
            </p>
          )}
        </div>

        {isLoading && <LoadingSpinner />}

        {error && (
          <div className="card bg-red-50 border border-red-200 text-red-700 text-sm">
            Failed to load dashboard data.
          </div>
        )}

        {data && (
          <>
            {/* Stat cards */}
            <section>
              <h3 className="section-title">Overview</h3>
              <div className="grid grid-cols-2 gap-3">
                <StatCard
                  title="Attendance"
                  value={`${data.attendance?.percentage ?? 0}%`}
                  subtitle={`${data.attendance?.present ?? 0} / ${data.attendance?.total ?? 0} days`}
                  icon="📅"
                  accent={
                    (data.attendance?.percentage ?? 0) >= 90 ? 'green'
                    : (data.attendance?.percentage ?? 0) >= 75 ? 'orange'
                    : 'red'
                  }
                />
                <StatCard
                  title="Avg. Score"
                  value={
                    data.recent_results?.length
                      ? `${Math.round(data.recent_results.reduce((s, r) => s + (r.marks_obtained / r.total_marks) * 100, 0) / data.recent_results.length)}%`
                      : '—'
                  }
                  subtitle="Recent exams"
                  icon="📊"
                  accent="blue"
                />
              </div>
            </section>

            {/* Quick links */}
            <section>
              <h3 className="section-title">Quick Access</h3>
              <div className="grid grid-cols-3 gap-2">
                {[
                  { to: '/app/student/attendance', label: 'Attendance', icon: '📅' },
                  { to: '/app/student/results',    label: 'Results',    icon: '📊' },
                  { to: '/app/student/timetable',  label: 'Timetable',  icon: '🗓️' },
                ].map(item => (
                  <Link
                    key={item.to}
                    to={item.to}
                    className="card flex flex-col items-center gap-2 py-4 text-center hover:bg-primary-50 hover:border-primary-200 border border-gray-100 transition-colors"
                  >
                    <span className="text-2xl">{item.icon}</span>
                    <span className="text-xs font-medium text-gray-700">{item.label}</span>
                  </Link>
                ))}
              </div>
            </section>

            {/* Recent results */}
            {data.recent_results && data.recent_results.length > 0 && (
              <section>
                <div className="flex items-center justify-between mb-2">
                  <h3 className="section-title mb-0">Recent Results</h3>
                  <Link to="/app/student/results" className="text-xs text-primary-600 font-medium">View all</Link>
                </div>
                <div className="card divide-y divide-gray-100 p-0 overflow-hidden">
                  {data.recent_results.map((r, i) => {
                    const pct = Math.round((r.marks_obtained / r.total_marks) * 100)
                    return (
                      <div key={i} className="flex items-center justify-between px-4 py-3">
                        <div>
                          <p className="text-sm font-medium text-gray-900">{r.subject_name}</p>
                          <p className="text-xs text-gray-400">{r.exam_name}</p>
                        </div>
                        <div className="text-right">
                          <p className="text-sm font-bold text-gray-800">
                            {r.marks_obtained}/{r.total_marks}
                          </p>
                          <span className={`badge ${pct >= 80 ? 'badge-green' : pct >= 50 ? 'badge-yellow' : 'badge-red'}`}>
                            {pct}%
                          </span>
                        </div>
                      </div>
                    )
                  })}
                </div>
              </section>
            )}

            {/* Upcoming exams */}
            {data.upcoming_exams && data.upcoming_exams.length > 0 && (
              <section>
                <h3 className="section-title">Upcoming Exams</h3>
                <div className="space-y-2">
                  {data.upcoming_exams.map((ex, i) => (
                    <div key={i} className="card flex items-center gap-3">
                      <div className="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center text-lg flex-shrink-0">📝</div>
                      <div className="min-w-0">
                        <p className="text-sm font-medium text-gray-900 truncate">{ex.subject_name}</p>
                        <p className="text-xs text-gray-500">{ex.exam_name} &middot; {ex.exam_date}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </section>
            )}

            {/* Notices */}
            {data.notices && data.notices.length > 0 && (
              <section>
                <div className="flex items-center justify-between mb-2">
                  <h3 className="section-title mb-0">Latest Notices</h3>
                  <Link to="/app/notices" className="text-xs text-primary-600 font-medium">View all</Link>
                </div>
                <div className="space-y-2">
                  {data.notices.map(n => (
                    <div key={n.id} className="card">
                      <p className="text-sm font-semibold text-gray-900">{n.title}</p>
                      <p className="text-xs text-gray-500 mt-1 line-clamp-2">{n.message ?? n.body}</p>
                    </div>
                  ))}
                </div>
              </section>
            )}
          </>
        )}
      </div>
    </Layout>
  )
}
