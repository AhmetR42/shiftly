import { formatHours } from '../lib/dates';

interface Props {
  value: number;
  max: number;
  label: string;
}

const RADIUS = 80;
const ARC = Math.PI * RADIUS; // length of the half circle

/**
 * Half-circle gauge: planned hours against contract hours.
 * Turns amber when someone is planned over their contract.
 */
export function Gauge({ value, max, label }: Props) {
  const ratio = max > 0 ? Math.min(value / max, 1) : 0;
  const over = value > max;

  return (
    <figure className={`gauge${over ? ' gauge--over' : ''}`}>
      <svg viewBox="0 0 200 112" role="img" aria-label={`${formatHours(value)} / ${max} ${label}`}>
        <path className="gauge__track" d="M20 100 A80 80 0 0 1 180 100" />
        <path
          className="gauge__value"
          d="M20 100 A80 80 0 0 1 180 100"
          strokeDasharray={ARC}
          strokeDashoffset={ARC * (1 - ratio)}
          data-testid="gauge-value"
        />
        <text x="100" y="86" textAnchor="middle" className="gauge__number">
          {formatHours(value)}
        </text>
        <text x="100" y="106" textAnchor="middle" className="gauge__label">
          / {max} {label}
        </text>
      </svg>
    </figure>
  );
}
