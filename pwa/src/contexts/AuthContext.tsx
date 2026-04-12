import React, { createContext, useContext, useState, useEffect, useCallback } from 'react'
import {
  apiLogin,
  apiLogout,
  saveToken,
  getToken,
  clearToken,
  LoginPayload,
  LoginResponse,
  ChildInfo,
} from '../api/client'

interface AuthState {
  isAuthenticated: boolean
  isLoading: boolean
  role: 'student' | 'parent' | null
  user: LoginResponse['user'] | null
  student: LoginResponse['student'] | null
  guardian: LoginResponse['guardian'] | null
  children: ChildInfo[]
  // For parent: the currently selected child
  activeChild: ChildInfo | null
}

interface AuthContextValue extends AuthState {
  login: (payload: LoginPayload) => Promise<void>
  logout: () => Promise<void>
  setActiveChild: (child: ChildInfo) => void
}

const AuthContext = createContext<AuthContextValue | null>(null)

const SESSION_KEY = 'pwa_session'

function loadSession(): Omit<AuthState, 'isLoading'> | null {
  try {
    const raw = localStorage.getItem(SESSION_KEY)
    if (!raw || !getToken()) return null
    return JSON.parse(raw)
  } catch {
    return null
  }
}

function saveSession(state: Omit<AuthState, 'isLoading'>): void {
  localStorage.setItem(SESSION_KEY, JSON.stringify(state))
}

function clearSession(): void {
  localStorage.removeItem(SESSION_KEY)
}

export function AuthProvider({ children: providerChildren }: { children: React.ReactNode }) {
  const [state, setState] = useState<AuthState>({
    isAuthenticated: false,
    isLoading: true,
    role: null,
    user: null,
    student: null,
    guardian: null,
    children: [],
    activeChild: null,
  })

  // Restore session on mount
  useEffect(() => {
    const session = loadSession()
    if (session && session.isAuthenticated) {
      setState({ ...session, isLoading: false })
    } else {
      setState(prev => ({ ...prev, isLoading: false }))
    }
  }, [])

  const login = useCallback(async (payload: LoginPayload) => {
    const res = await apiLogin(payload)
    saveToken(res.token)

    const firstChild = res.children?.[0] ?? null
    const newState: Omit<AuthState, 'isLoading'> = {
      isAuthenticated: true,
      role: res.role,
      user: res.user,
      student: res.student ?? null,
      guardian: res.guardian ?? null,
      children: res.children ?? [],
      activeChild: firstChild,
    }
    saveSession(newState)
    setState({ ...newState, isLoading: false })
  }, [])

  const logout = useCallback(async () => {
    try { await apiLogout() } catch { /* ignore */ }
    clearToken()
    clearSession()
    setState({
      isAuthenticated: false,
      isLoading: false,
      role: null,
      user: null,
      student: null,
      guardian: null,
      children: [],
      activeChild: null,
    })
  }, [])

  const setActiveChild = useCallback((child: ChildInfo) => {
    setState(prev => {
      const next = { ...prev, activeChild: child }
      saveSession({ ...next, isLoading: undefined as any })
      return next
    })
  }, [])

  return (
    <AuthContext.Provider value={{ ...state, login, logout, setActiveChild }}>
      {providerChildren}
    </AuthContext.Provider>
  )
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
