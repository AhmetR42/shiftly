/**
 * The mark is three shift bars on a timeline, staggered like a rota,
 * which also happen to read as an "S".
 */
export function LogoMark({ size = 32 }: { size?: number }) {
  return (
    <svg width={size} height={size} viewBox="0 0 32 32" aria-hidden="true" className="logo-mark">
      <rect width="32" height="32" rx="9" fill="var(--accent)" />
      <rect x="11" y="7.5" width="14" height="4.5" rx="2.25" fill="#fff" />
      <rect x="7" y="13.75" width="18" height="4.5" rx="2.25" fill="#fff" opacity=".55" />
      <rect x="7" y="20" width="14" height="4.5" rx="2.25" fill="#fff" />
    </svg>
  );
}

export function Logo({ size = 32, light = false }: { size?: number; light?: boolean }) {
  return (
    <span className={`logo${light ? ' logo--light' : ''}`}>
      <LogoMark size={size} />
      <span className="logo-word">
        <b>SHIFT</b>
        <span>ly</span>
      </span>
    </span>
  );
}
