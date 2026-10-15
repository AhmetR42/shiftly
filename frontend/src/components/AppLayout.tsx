import { useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { Sidebar } from './Sidebar';
import { Topbar } from './Topbar';

export interface LayoutContext {
  search: string;
}

export function AppLayout() {
  const [search, setSearch] = useState('');
  const location = useLocation();

  return (
    <div className="app">
      <Sidebar />
      <div className="app__main">
        <Topbar search={search} onSearch={setSearch} />
        {/* Keyed on the path so every page change replays the fade-in */}
        <main className="page" key={location.pathname}>
          <Outlet context={{ search } satisfies LayoutContext} />
        </main>
      </div>
    </div>
  );
}
