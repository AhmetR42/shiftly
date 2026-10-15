import { en } from './en';
import { nl } from './nl';
import { translate } from './I18nContext';

describe('translations', () => {
  it('has a Dutch string for every English key', () => {
    expect(Object.keys(nl).sort()).toEqual(Object.keys(en).sort());
  });

  it('keeps the same placeholders in both languages', () => {
    const vars = (s: string) => (s.match(/\{\w+\}/g) ?? []).sort();
    for (const key of Object.keys(en) as (keyof typeof en)[]) {
      expect(vars(nl[key]), key).toEqual(vars(en[key]));
    }
  });

  it('fills in placeholders', () => {
    expect(translate('nl', 'week.number', { n: 42 })).toBe('Week 42');
  });
});
