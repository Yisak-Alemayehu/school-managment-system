import { BrowserRouter, Routes, Route, Navigate, useLocation } from 'react-router-dom'
import { useAuth } from './contexts/AuthContext'
import Login from './pages/Login'
import StudentDashboard from './pages/student/Dashboard'
import StudentAttendance from './pages/student/Attendance'
import StudentResults from './pages/student/Results'
import StudentTimetable from './pages/student/Timetable'
import ParentDashboard from './pages/parent/Dashboard'
import ParentFees from './pages/parent/Fees'
import Messages from './pages/shared/Messages'
import Notices from './pages/shared/Notices'
import Profile from './pages/shared/Profile'
import LoadingSpinner from './components/LoadingSpinner'

function RequireAuth({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isLoading } = useAuth()
  const location = useLocation()
  if (isLoading) return <LoadingSpinner fullScreen />
  if (!isAuthenticated) return <Navigate to="/app/login" state={{ from: location }} replace />
  return <>{children}</>
}

function RoleRedirect() {
  const { role } = useAuth()
  if (role === 'parent') return <Navigate to="/app/parent" replace />
  return <Navigate to="/app/student" replace />
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Public */}
        <Route path="/app/login" element={<Login />} />

        {/* Protected student routes */}
        <Route path="/app/student" element={<RequireAuth><StudentDashboard /></RequireAuth>} />
        <Route path="/app/student/attendance" element={<RequireAuth><StudentAttendance /></RequireAuth>} />
        <Route path="/app/student/results" element={<RequireAuth><StudentResults /></RequireAuth>} />
        <Route path="/app/student/timetable" element={<RequireAuth><StudentTimetable /></RequireAuth>} />

        {/* Protected parent routes */}
        <Route path="/app/parent" element={<RequireAuth><ParentDashboard /></RequireAuth>} />
        <Route path="/app/parent/fees" element={<RequireAuth><ParentFees /></RequireAuth>} />

        {/* Shared protected routes */}
        <Route path="/app/messages" element={<RequireAuth><Messages /></RequireAuth>} />
        <Route path="/app/notices" element={<RequireAuth><Notices /></RequireAuth>} />
        <Route path="/app/profile" element={<RequireAuth><Profile /></RequireAuth>} />

        {/* Default redirect */}
        <Route path="/app" element={<RequireAuth><RoleRedirect /></RequireAuth>} />
        <Route path="*" element={<Navigate to="/app" replace />} />
      </Routes>
    </BrowserRouter>
  )
}
