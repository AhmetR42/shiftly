import { useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api/client';
import { useI18n } from '../i18n/I18nContext';
import { AuthShell } from './LoginPage';

export function ForgotPasswordPage() {
  const { t } = useI18n();
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    try {
      setMessage((await api.forgotPassword(email)).message);
    } catch {
      setMessage(t('common.error'));
    } finally {
      setBusy(false);
    }
  };

  return (
    <AuthShell>
      <form className="form" onSubmit={submit}>
        <h1>{t('forgot.title')}</h1>
        <p className="muted">{t('forgot.text')}</p>
        <label className="field">
          <span>{t('login.email')}</span>
          <input type="email" required value={email} onChange={(e) => setEmail(e.target.value)} />
        </label>
        {message && <p className="notice" role="status">{message}</p>}
        <button type="submit" className="button button--block" disabled={busy}>
          {t('forgot.submit')}
        </button>
        <Link to="/login" className="link">
          {t('forgot.back')}
        </Link>
      </form>
    </AuthShell>
  );
}
