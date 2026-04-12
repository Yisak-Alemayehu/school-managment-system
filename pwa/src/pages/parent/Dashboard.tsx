import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import Layout from '../../components/Layout'
import StatCard from '../../components/StatCard'
import LoadingSpinner from '../../components/LoadingSpinner'
import { apiParentDashboard, apiParentStudent, ChildInfo } from '../../api/client'
import { useAuth } from '../../contexts/AuthContext'

function ChildChips() {
  const { children, activeChild, setActiveChild, guardian } = useAuth()
  if (!children.length) return null
  return (
    <div className="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4">
      {children.map(c => (
        <button
          key={c.student_id}
          onClick={() => setActiveChild(c)}
          className={`flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium border transition-colors ${
            activeChild?.student_id === c.student_id
              ? 'bg-primary-600 text-white border-primary-600'
              : 'bg-white text-gray-700 border-gray-200'
          }`}
        >
          {c.first_name}
        </button>
      ))}
    </div>
  )
}

function ChildSummary({ childInfo, guardianId }: { childInfo: ChildInfo; guardianId: number }) {
  const { data, isLoading } = useQuery({
    queryKey: ['parent-student', guardianId, childInfo.student_id],
    queryFn: () => apiParentStudent(guardianId, childInfo.student_id),
    enabled: !!guardianId && !!childInfo.student_id,
  })

  if (isLoading) return <LoadingSpinner />

  const att = data?.attendance
  const recentMarks = data?.marks?.slice(0, 3) ?? []

  return (
    <div className="space-y-5">
      {/* Enrollment info */}
      {data?.enrollment && (
        <p className="text-sm text-gray-500">
          {data.enrollment.grade_name} {data.enrollment.section_name} &middot; {data.enrollment.academic_year}
        </p>
      )}

      {/* Attendance */}
      {att && (
        <section>
          <h3 className="section-title">Attendance</h3>
          <div className="grid grid-cols-2 gap-3">
            <StatCard title="Rate" value={`${att.percentage}%`} subtitle={`${att.present}/${att.total} days`} icon="📅" accent={att.percentage >= 90 ? 'green' : att.percentage >= 75 ? 'orange' : 'red'} />
            <StatCard title="Absent" value={att.absent} subtitle={`Late: ${att.late ?? 0}`} icon="❌" accent="red" />
          </div>
        </section>
      )}

      {/* Recent marks */}
      {recentMarks.length > 0 && (
        <section>
          <h3 className="section-title">Recent Results</h3>
          <div className="card divide-y divide-gray-100 p-0 overflow-hidden">
            {recentMarks.map((m, i) => {
              const pct = Math.round((m.marks_obtained / m.total_marks) * 100)
              return (
                <div key={i} className="flex items-center justify-between px-4 py-3">
                  <div>
                    <p className="text-sm font-medium text-gray-900">{m.subject_name}</p>
                    <p className="text-xs text-gray-400">{m.exam_name}</p>
                  </div>
                  <span className={`badge ${pct >= 80 ? 'badge-green' : pct >= 50 ? 'badge-yellow' : 'badge-red'}`}>
                    {m.marks_obtained}/{m.total_marks}
                  </span>
                </div>
              )
            })}
          </div>
        </section>
      )}

      {/* Fee balance */}
      {data?.fee_balance !== undefined && (
        <section>
          <h3 className="section-title">Fees</h3>
          <div className={`card flex items-center justify-between ${data.fee_balance > 0 ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200'}`}>
            <span className="text-sm font-medium text-gray-700">Outstanding Balance</span>
            <span className={`font-bold text-lg ${data.fee_balance > 0 ? 'text-red-600' : 'text-green-600'}`}>
              {data.fee_balance > 0 ? `${data.fee_balance.toFixed(2)} ETB` : 'Paid'}
            </span>
          </div>
          <Link to="/app/parent/fees" className="btn-secondary w-full mt-2 text-center block">
            View Fee Details
          </Link>
        </section>
      )}
    </div>
  )
}

export default function ParentDashboard() {
  const { guardian, activeChild } = useAuth()

  const { data: dashData, isLoading: dashLoading } = useQuery({
    queryKey: ['parent-dashboard'],
    queryFn: apiParentDashboard,
  })

  return (
    <Layout title="Dashboard">
      <div className="space-y-5">
        {/* Greeting */}
        <div>
          <p className="text-gray-400 text-sm">Welcome back,</p>
          <h2 className="text-xl font-bold text-gray-900">{guardian?.full_name ?? 'Parent'}</h2>
        </div>

        {/* Child switcher */}
        <ChildChips />

        {dashLoading && <LoadingSpinner />}

        {/* Active child data */}
        {activeChild && guardian && (
          <ChildSummary childInfo={activeChild} guardianId={guardian.id} />
        )}

        {/* Notices */}
        {dashData?.notices && dashData.notices.length > 0 && (
          <section>
            <div className="flex items-center justify-between mb-2">
              <h3 className="section-title mb-0">Latest Notices</h3>
              <Link to="/app/notices" className="text-xs text-primary-600 font-medium">View all</Link>
            </div>
            <div className="space-y-2">
              {dashData.notices.map(n => (
                <div key={n.id} className="card">
                  <p className="text-sm font-semibold text-gray-900">{n.title}</p>
                  <p className="text-xs text-gray-500 mt-1 line-clamp-2">{n.message ?? n.body}</p>
                </div>
              ))}
            </div>
          </section>
        )}
      </div>
    </Layout>
  )
}
