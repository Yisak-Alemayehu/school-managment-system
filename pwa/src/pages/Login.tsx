import { useState, FormEvent } from 'react'
import { useNavigate, useLocation } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'

export default function Login() {
  const { login, isAuthenticated, role } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()

  const from: string = (location.state as any)?.from?.pathname ?? null

  const [selectedRole, setSelectedRole] = useState<'student' | 'parent'>('student')
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  // Already authenticated — redirect
  if (isAuthenticated) {
    const dest = from || (role === 'parent' ? '/app/parent' : '/app/student')
    navigate(dest, { replace: true })
    return null
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    if (!username.trim() || !password) return
    setError('')
    setLoading(true)
    try {
      await login({ username: username.trim(), password, role: selectedRole })
      const dest = from || (selectedRole === 'parent' ? '/app/parent' : '/app/student')
      navigate(dest, { replace: true })
    } catch (err: any) {
      setError(err?.error ?? 'Login failed. Please check your credentials.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-primary-600 to-primary-800 px-6">
      {/* Logo / branding */}
      <div className="mb-8 text-center">
        <div className="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
          <svg className="w-12 h-12 text-primary-600" fill="none" stroke="currentColor" strokeWidth="1.5" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
          </svg>
        </div>
        <h1 className="text-2xl font-bold text-white">School Portal</h1>
        <p className="text-primary-200 text-sm mt-1">Student & Parent Dashboard</p>
      </div>

      {/* Card */}
      <div className="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6">
        {/* Role selector */}
        <div className="flex rounded-xl bg-gray-100 p-1 mb-6">
          {(['student', 'parent'] as const).map(r => (
            <button
              key={r}
              type="button"
              onClick={() => setSelectedRole(r)}
              className={`flex-1 py-2 rounded-lg text-sm font-semibold transition-all ${
                selectedRole === r
                  ? 'bg-white text-primary-700 shadow'
                  : 'text-gray-500 hover:text-gray-700'
              }`}
            >
              {r === 'student' ? '🎒 Student' : '👨‍👧 Parent'}
            </button>
          ))}
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {selectedRole === 'student' ? 'Student ID / Username' : 'Username / Email'}
            </label>
            <input
              className="input"
              type="text"
              autoComplete="username"
              value={username}
              onChange={e => setUsername(e.target.value)}
              placeholder={selectedRole === 'student' ? 'e.g. student001' : 'e.g. parent@email.com'}
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input
              className="input"
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={e => setPassword(e.target.value)}
              placeholder="••••••••"
              required
            />
          </div>

          {error && (
            <div className="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-3 py-2">
              {error}
            </div>
          )}

          <button
            type="submit"
            disabled={loading || !username.trim() || !password}
            className="btn-primary w-full"
          >
            {loading ? (
              <span className="flex items-center justify-center gap-2">
                <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                Signing in…
              </span>
            ) : 'Sign In'}
          </button>
        </form>
      </div>

      <p className="text-primary-200 text-xs mt-8">
        Contact your school administrator if you cannot log in.
      </p>
    </div>
  )
}
