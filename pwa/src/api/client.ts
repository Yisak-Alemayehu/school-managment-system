/**
 * PWA API Client
 * Communicates with /pwa-api/* endpoints on the same PHP server.
 * Attaches the Bearer token from localStorage automatically.
 */

const TOKEN_KEY = 'pwa_token'

// Always derive API base from the current page origin to avoid mixed-content
// errors when the server config (APP_URL) uses http:// but the site is on HTTPS.
function getApiBase(): string {
  return window.location.origin
}

// ─── Token helpers ──────────────────────────────────────────
export function saveToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token)
}

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function clearToken(): void {
  localStorage.removeItem(TOKEN_KEY)
}

// ─── Fetch wrapper ──────────────────────────────────────────
export interface ApiError {
  error: string
  status: number
}

async function request<T>(
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const token = getToken()
  const base = getApiBase()

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(options.headers as Record<string, string> | undefined),
  }

  const res = await fetch(`${base}/pwa-api/${path}`, {
    ...options,
    headers,
  })

  const data = await res.json().catch(() => ({}))

  if (!res.ok) {
    const err: ApiError = { error: data.error ?? 'An error occurred', status: res.status }
    throw err
  }

  return data as T
}

// ─── Auth endpoints ─────────────────────────────────────────
export interface LoginPayload {
  username: string
  password: string
  role: 'student' | 'parent'
}

export interface LoginResponse {
  token: string
  role: 'student' | 'parent'
  user: UserInfo
  student?: StudentInfo
  guardian?: GuardianInfo
  children?: ChildInfo[]
}

export interface UserInfo {
  id: number
  name: string
  username: string
  email: string
  avatar: string | null
  phone?: string | null
}

export interface StudentInfo {
  id: number
  full_name: string
  first_name?: string
  last_name?: string
  photo: string | null
  class_name: string | null
  section_name: string | null
  class_id: number | null
  section_id: number | null
  roll_no: string | null
  gender?: string
  dob?: string
  admission_no?: string
  session_name?: string
  status?: string
}

export interface GuardianInfo {
  id: number
  name: string
  relation: string
  photo: string | null
}

export interface ChildInfo {
  id: number
  full_name: string
  photo: string | null
  class_name: string | null
  section_name: string | null
  status: string
}

export const apiLogin = (payload: LoginPayload) =>
  request<LoginResponse>('login', {
    method: 'POST',
    body: JSON.stringify(payload),
  })

export const apiLogout = () =>
  request('logout', { method: 'POST' })

export const apiMe = () =>
  request<UserInfo & { role: string; student?: StudentInfo; guardian?: GuardianInfo }>('me')

// ─── Student endpoints ───────────────────────────────────────
export interface AttendanceSummary {
  present: number
  absent: number
  late: number
  excused?: number
  half_day?: number
  total: number
  percentage: number
}

export interface Dashboard {
  enrollment: EnrollmentInfo | null
  attendance: AttendanceSummary
  recent_results: ResultRow[]
  notices: NoticeItem[]
  upcoming_exams: ExamItem[]
}

export interface EnrollmentInfo {
  class_name: string
  section_name: string | null
  session_name: string
  term_name: string | null
  roll_no: string | null
  class_id: number
  section_id: number | null
  session_id: number
  term_id: number | null
}

export interface ResultRow {
  marks_obtained: number | null
  max_marks: number
  is_absent: boolean
  subject_name: string
  exam_name: string
  exam_type: string
  percentage?: number | null
}

export interface NoticeItem {
  id: number
  title: string
  content: string
  created_at: string
  author?: string
  audience?: string
}

export interface ExamItem {
  exam_date: string
  start_time: string
  end_time: string
  subject_name: string
  exam_name: string
}

export const apiStudentDashboard = () =>
  request<Dashboard>('student-dashboard')

export const apiStudentAttendance = (month?: string) =>
  request<{ month: string; records: AttendanceRecord[]; summary: AttendanceSummary }>(
    `student-attendance${month ? `?month=${month}` : ''}`,
  )

export interface AttendanceRecord {
  date: string
  status: 'present' | 'absent' | 'late' | 'excused' | 'half_day'
  remarks: string | null
  subject_name: string
}

export const apiStudentResults = (examId?: number) =>
  request<{
    exams: { id: number; name: string; type: string; start_date: string; status: string }[]
    selected_exam_id: number | null
    marks: (ResultRow & { subject_code: string })[]
    report_card: ReportCard | null
    session: EnrollmentInfo | null
  }>(`student-results${examId ? `?exam_id=${examId}` : ''}`)

export interface ReportCard {
  percentage: number
  grade: string
  rank: number | null
  total_marks: number
  total_max_marks: number
  teacher_remarks: string | null
  status: string
}

export const apiStudentTimetable = () =>
  request<{
    class_id: number
    section_id: number | null
    timetable: Record<string, TimetableSlot[]>
  }>('student-timetable')

export interface TimetableSlot {
  day_of_week: string
  start_time: string
  end_time: string
  room: string | null
  subject_name: string
  subject_code: string
  teacher_name: string | null
}

// ─── Parent endpoints ────────────────────────────────────────
export const apiParentDashboard = () =>
  request<{ children: ChildInfo[]; notices: NoticeItem[] }>('parent-dashboard')

export const apiParentChildren = () =>
  request<{ children: (ChildInfo & { gender: string; dob: string; admission_no: string; is_primary: boolean })[] }>(
    'parent-children',
  )

export const apiParentStudent = (id: number) =>
  request<{
    student: StudentInfo
    attendance: AttendanceSummary
    latest_marks: ResultRow[]
    fee_balance: { total: number; paid: number; due: number }
  }>(`parent-student/${id}`)

export const apiParentFees = (studentId: number) =>
  request<{
    fees: FeeRecord[]
    totals: { total: number; paid: number; balance: number }
    transactions: Transaction[]
  }>(`parent-fees?student_id=${studentId}`)

export interface FeeRecord {
  id: number
  fee_name: string
  fee_description: string | null
  frequency: string
  amount: number
  amount_paid: number
  balance: number
  status: string
  due_date: string | null
  created_at: string
}

export interface Transaction {
  amount: number
  method: string
  reference_no: string | null
  note: string | null
  created_at: string
  fee_name: string
}

// ─── Shared endpoints ────────────────────────────────────────
export const apiNotices = (page = 1) =>
  request<{ notices: NoticeItem[]; pagination: Pagination }>(`notices?page=${page}`)

export interface Pagination {
  page: number
  limit: number
  total: number
  total_pages: number
}

export const apiMessages = (page = 1) =>
  request<{ messages: Message[]; unread_count: number; page: number }>(`messages?page=${page}`)

export const apiSendMessage = (data: { receiver_id: number; subject: string; body: string }) =>
  request<{ message: string; id: number }>('messages-send', {
    method: 'POST',
    body: JSON.stringify(data),
  })

export interface Message {
  id: number
  subject: string
  body: string
  is_read: boolean
  created_at: string
  sender_name: string | null
  sender_id: number
  receiver_id: number
}
