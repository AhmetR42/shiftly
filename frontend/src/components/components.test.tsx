import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { renderWithProviders } from '../test/render';
import { Gauge } from './Gauge';
import { LanguageToggle } from './LanguageToggle';
import { ShiftModal } from './ShiftModal';
import { useI18n } from '../i18n/I18nContext';
import type { Employee, Shift } from '../api/types';

function Greeting() {
  const { t } = useI18n();
  return <p>{t('topbar.hello', { name: 'Sanne' })}</p>;
}

describe('LanguageToggle', () => {
  it('switches the interface language', async () => {
    renderWithProviders(
      <>
        <LanguageToggle />
        <Greeting />
      </>,
    );
    expect(screen.getByText('Hello, Sanne')).toBeInTheDocument();
    await userEvent.click(screen.getByRole('radio', { name: 'NL' }));
    expect(screen.getByText('Hallo, Sanne')).toBeInTheDocument();
    expect(screen.getByRole('radio', { name: 'NL' })).toHaveAttribute('aria-checked', 'true');
  });
});

describe('Gauge', () => {
  it('fills relative to the contract and flags overtime', () => {
    const { container } = renderWithProviders(<Gauge value={30} max={24} label="hours" />);
    expect(container.querySelector('.gauge--over')).not.toBeNull();
    expect(screen.getByRole('img')).toHaveAccessibleName('30 / 24 hours');
  });
});

describe('ShiftModal', () => {
  const employees: Employee[] = [
    { id: 1, name: 'Emma Smit', department: 'checkout', contract_hours: 8, planned_hours: 6, availability: [] },
  ];
  const weekShifts: Shift[] = [
    { id: 9, user_id: 1, date: '2026-10-12', start_time: '08:00', end_time: '14:00', department: 'checkout', note: null, hours: 6 },
  ];

  it('warns live about overlaps and contract hours before saving', () => {
    renderWithProviders(
      <ShiftModal employees={employees} weekShifts={weekShifts} defaults={{ user_id: 1, date: '2026-10-12' }} onClose={() => {}} />,
    );
    expect(screen.getByText('Overlaps with another shift')).toBeInTheDocument();
    expect(screen.getByText('12 hours planned, contract is 8')).toBeInTheDocument();
  });

  it('blocks saving when the end is before the start', async () => {
    renderWithProviders(<ShiftModal employees={employees} weekShifts={[]} defaults={{ date: '2026-10-13' }} onClose={() => {}} />);
    const end = screen.getByLabelText('End');
    await userEvent.clear(end);
    await userEvent.type(end, '07:00');
    expect(screen.getByRole('button', { name: 'Save' })).toBeDisabled();
  });
});
