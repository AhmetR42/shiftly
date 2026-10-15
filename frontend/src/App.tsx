import { Navigate, Route, Routes } from 'react-router-dom';
import { useAuth } from './auth/AuthContext';
import { AppLayout } from './components/AppLayout';
import { AvailabilityPage } from './pages/AvailabilityPage';
import { DashboardPage } from './pages/DashboardPage';
import { ForgotPasswordPage } from './pages/ForgotPasswordPage';
import { LoginPage } from './pages/LoginPage';
import { MyWeekPage } from './pages/MyWeekPage';
import { RotaPage } from './pages/RotaPage';
import { SwapsPage } from './pages/SwapsPage';
import { TimeOffPage } from './pages/TimeOffPage';

export function App() {
  const { user, loading } = useAuth();

  if (loading) return <div className="splash" aria-busy="true" />;

  if (!user) {
    return (
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    );
  }

  const manager = user.role === 'manager';

  return (
    <Routes>
      <Route element={<AppLayout />}>
        <Route index element={<DashboardPage />} />
        <Route path="rota" element={manager ? <RotaPage /> : <Navigate to="/my-week" replace />} />
        <Route path="my-week" element={<MyWeekPage />} />
        <Route path="swaps" element={<SwapsPage />} />
        <Route path="time-off" element={<TimeOffPage />} />
        <Route path="availability" element={<AvailabilityPage />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
