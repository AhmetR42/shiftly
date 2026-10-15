import { useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { ApiError } from '../api/client';
import type { Role } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { LanguageToggle } from '../components/LanguageToggle';
import { Logo } from '../components/Logo';
import { ThemeToggle } from '../components/ThemeToggle';
import { useI18n } from '../i18n/I18nContext';

export function AuthShell({ children }: { children: React.ReactNode }) {
  const { t } = useI18n();
  return (
    <div className="auth">
      <section className="auth__panel">
        <div className="auth__top">
          <Logo />
          <span className="spacer" />
          <LanguageToggle />
          <ThemeToggle />
        </div>
        <div className="auth__body">{children}</div>
      </section>
      <aside className="auth__hero" aria-hidden="true">
        <div>
          <h2>{t('login.title')}</h2>
          <p>{t('login.subtitle')}</p>
          <div className="auth__bars">
            {[['20%', '45%'], ['5%', '50%'], ['35%', '40%'], ['10%', '35%'], ['45%', '45%']].map(([left, width], i) => (
              <span key={i} style={{ marginLeft: left, width, ['--i' as string]: i }} />
            ))}
          </div>
        </div>
      </aside>
    </div>
  );
}

export function LoginPage() {
  const { t } = useI18n();
  const { login, startDemo } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState<string | null>(null);

  const run = async (key: string, action: () => Promise<unknown>) => {
    setBusy(key);
    setError(null);
    try {
      await action();
    } catch (err) {
      setError(err instanceof ApiError ? err.firstError() : t('common.error'));
      setBusy(null);
    }
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    run('login', () => login(email, password));
  };
  const demo = (role: Role) => run(role, () => startDemo(role));

  return (
    <AuthShell>
      <form className="form" onSubmit={submit}>
        <h1>{t('login.submit')}</h1>
        <label className="field">
          <span>{t('login.email')}</span>
          <input type="email" required autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} />
        </label>
        <label className="field">
          <span>{t('login.password')}</span>
          <input
            type="password"
            required
            autoComplete="current-password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
        </label>
        {error && <p className="form__error" role="alert">{error}</p>}
        <button type="submit" className="button button--block" disabled={busy !== null}>
          {busy === 'login' ? t('common.loading') : t('login.submit')}
        </button>
        <Link to="/forgot-password" className="link">
          {t('login.forgot')}
        </Link>
      </form>

      <div className="auth__demo">
        <p className="divider">{t('login.demo')}</p>
        <div className="auth__demo-buttons">
          <button type="button" className="button button--ghost" disabled={busy !== null} onClick={() => demo('manager')}>
            {busy === 'manager' ? t('common.loading') : t('login.demoManager')}
          </button>
          <button type="button" className="button button--ghost" disabled={busy !== null} onClick={() => demo('employee')}>
            {busy === 'employee' ? t('common.loading') : t('login.demoEmployee')}
          </button>
        </div>
        <p className="muted small">{t('login.demoNote')}</p>
      </div>
    </AuthShell>
  );
}
