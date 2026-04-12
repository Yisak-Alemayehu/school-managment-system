import { useAuth } from '../../contexts/AuthContext'
import Layout from '../../components/Layout'

export default function Profile() {
  const { user, role, student, guardian, logout } = useAuth()

  async function handleLogout() {
    await logout()
  }

  return (
    <Layout title="Profile">
      <div className="space-y-5">
        {/* Avatar + name */}
        <div className="flex flex-col items-center py-4">
          <div className="w-20 h-20 rounded-full bg-primary-100 flex items-center justify-center text-3xl font-bold text-primary-700 mb-3">
            {user?.full_name?.charAt(0)?.toUpperCase() ?? '?'}
          </div>
          <h2 className="text-xl font-bold text-gray-900">{user?.full_name}</h2>
          <span className="badge badge-blue mt-1 capitalize">{role}</span>
        </div>

        {/* Account info */}
        <section>
          <h3 className="section-title">Account Info</h3>
          <div className="card divide-y divide-gray-100 p-0 overflow-hidden">
            <InfoRow label="Username" value={user?.username ?? '—'} />
            <InfoRow label="Email" value={user?.email ?? '—'} />
            <InfoRow label="Role" value={role ?? '—'} />
          </div>
        </section>

        {/* Student-specific info */}
        {role === 'student' && student && (
          <section>
            <h3 className="section-title">Student Details</h3>
            <div className="card divide-y divide-gray-100 p-0 overflow-hidden">
              <InfoRow label="Full Name" value={student.full_name ?? '—'} />
              <InfoRow label="Date of Birth" value={student.date_of_birth ?? '—'} />
              <InfoRow label="Gender" value={student.gender ?? '—'} />
              <InfoRow label="Phone" value={student.phone ?? '—'} />
            </div>
          </section>
        )}

        {/* Guardian-specific info */}
        {role === 'parent' && guardian && (
          <section>
            <h3 className="section-title">Guardian Details</h3>
            <div className="card divide-y divide-gray-100 p-0 overflow-hidden">
              <InfoRow label="Full Name" value={guardian.full_name ?? '—'} />
              <InfoRow label="Phone" value={guardian.phone ?? '—'} />
              <InfoRow label="Relation" value={guardian.relation ?? '—'} />
            </div>
          </section>
        )}

        {/* Sign out */}
        <button
          onClick={handleLogout}
          className="w-full py-3 rounded-xl border-2 border-red-200 text-red-600 font-semibold text-sm hover:bg-red-50 transition-colors"
        >
          Sign Out
        </button>

        <p className="text-center text-xs text-gray-400 pb-2">
          School Portal &copy; {new Date().getFullYear()}
        </p>
      </div>
    </Layout>
  )
}

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between items-center px-4 py-3">
      <span className="text-sm text-gray-500">{label}</span>
      <span className="text-sm font-medium text-gray-900 text-right max-w-[60%] truncate">{value}</span>
    </div>
  )
}
