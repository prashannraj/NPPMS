import DashboardStats from '@/components/dashboard/DashboardStats';
import RecentProjects from '@/components/dashboard/RecentProjects';
import ProcurementTimeline from '@/components/dashboard/ProcurementTimeline';

export default function Home() {
  return (
    <div className="space-y-6">
      <div className="bg-white rounded-lg shadow p-6">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">
          Nepal Public Procurement Management System
        </h1>
        <p className="text-gray-600 mb-4">
          Digitizing public procurement processes for Provincial and Local governments of Nepal
        </p>
        <div className="flex space-x-4">
          <button className="bg-primary-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-700">
            Create New Project
          </button>
          <button className="bg-white text-primary-600 border border-primary-600 px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-50">
            View Procurement Plans
          </button>
          <button className="bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50">
            Generate Reports
          </button>
        </div>
      </div>

      <DashboardStats />

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <RecentProjects />
        <ProcurementTimeline />
      </div>

      <div className="bg-white rounded-lg shadow p-6">
        <h2 className="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <a href="/projects/create" className="bg-primary-50 border border-primary-200 rounded-lg p-4 text-center hover:bg-primary-100">
            <div className="text-primary-600 font-medium">Create Project</div>
          </a>
          <a href="/procurement/plan" className="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center hover:bg-blue-100">
            <div className="text-blue-600 font-medium">Procurement Plan</div>
          </a>
          <a href="/bidding/invitation" className="bg-green-50 border border-green-200 rounded-lg p-4 text-center hover:bg-green-100">
            <div className="text-green-600 font-medium">Bid Invitation</div>
          </a>
          <a href="/reports/financial" className="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center hover:bg-purple-100">
            <div className="text-purple-600 font-medium">Financial Reports</div>
          </a>
        </div>
      </div>
    </div>
  );
}