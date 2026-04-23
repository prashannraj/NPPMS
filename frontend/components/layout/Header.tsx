import React from 'react';
import { 
  Bell, 
  Search, 
  User,
  Menu,
  Home,
  FileText,
  BarChart3,
  Settings,
  LogOut
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const Header: React.FC = () => {
  return (
    <header className="sticky top-0 z-50 w-full border-b border-gray-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/60">
      <div className="container mx-auto px-4">
        <div className="flex h-16 items-center justify-between">
          {/* Logo and Brand */}
          <div className="flex items-center space-x-4">
            <button className="lg:hidden">
              <Menu className="h-6 w-6 text-gray-700" />
            </button>
            <div className="flex items-center space-x-2">
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600">
                <FileText className="h-5 w-5 text-white" />
              </div>
              <div>
                <h1 className="text-xl font-bold text-gray-900">NPPMS</h1>
                <p className="text-xs text-gray-500">Nepal Public Procurement Management System</p>
              </div>
            </div>
          </div>

          {/* Search Bar */}
          <div className="hidden flex-1 max-w-2xl mx-8 lg:block">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <input
                type="search"
                placeholder="Search projects, tenders, or documents..."
                className="w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-10 pr-4 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
          </div>

          {/* Right Side Actions */}
          <div className="flex items-center space-x-4">
            {/* Notifications */}
            <button className="relative rounded-full p-2 hover:bg-gray-100">
              <Bell className="h-5 w-5 text-gray-700" />
              <Badge className="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center rounded-full bg-red-500 text-xs text-white">
                3
              </Badge>
            </button>

            {/* User Profile */}
            <div className="flex items-center space-x-3">
              <div className="hidden flex-col text-right sm:flex">
                <span className="text-sm font-medium text-gray-900">राम बहादुर श्रेष्ठ</span>
                <span className="text-xs text-gray-500">Procurement Officer</span>
              </div>
              <div className="relative">
                <button className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-800">
                  <User className="h-5 w-5" />
                </button>
                <Badge className="absolute -bottom-1 -right-1 h-4 w-4 flex items-center justify-center rounded-full bg-green-500 text-xs text-white">
                  <span className="sr-only">Online</span>
                </Badge>
              </div>
            </div>
          </div>
        </div>

        {/* Secondary Navigation */}
        <div className="hidden h-12 items-center justify-between border-t border-gray-100 lg:flex">
          <nav className="flex space-x-1">
            <a
              href="/dashboard"
              className="flex items-center space-x-2 rounded-lg px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50"
            >
              <Home className="h-4 w-4" />
              <span>Dashboard</span>
            </a>
            <a
              href="/projects"
              className="flex items-center space-x-2 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
            >
              <FileText className="h-4 w-4" />
              <span>Projects</span>
            </a>
            <a
              href="/procurement"
              className="flex items-center space-x-2 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
            >
              <BarChart3 className="h-4 w-4" />
              <span>Procurement</span>
            </a>
            <a
              href="/reports"
              className="flex items-center space-x-2 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
            >
              <FileText className="h-4 w-4" />
              <span>Reports</span>
            </a>
            <a
              href="/settings"
              className="flex items-center space-x-2 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
            >
              <Settings className="h-4 w-4" />
              <span>Settings</span>
            </a>
          </nav>

          <div className="flex items-center space-x-4">
            <div className="text-sm text-gray-600">
              <span className="font-medium">Fiscal Year:</span> 2081/82
            </div>
            <Badge variant="outline" className="text-xs">
              <span className="mr-1">🇳🇵</span>
              नेपाली
            </Badge>
            <button className="flex items-center space-x-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
              <LogOut className="h-4 w-4" />
              <span>Logout</span>
            </button>
          </div>
        </div>
      </div>
    </header>
  );
};

export default Header;