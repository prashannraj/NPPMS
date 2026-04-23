import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { 
  TrendingUp, 
  FileText, 
  Clock, 
  CheckCircle,
  AlertTriangle,
  DollarSign
} from 'lucide-react';

interface StatCardProps {
  title: string;
  value: string | number;
  change?: string;
  icon: React.ReactNode;
  color: string;
}

const StatCard: React.FC<StatCardProps> = ({ title, value, change, icon, color }) => {
  return (
    <Card className="border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium text-gray-600">
          {title}
        </CardTitle>
        <div className={`p-2 rounded-full ${color}`}>
          {icon}
        </div>
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold">{value}</div>
        {change && (
          <p className="text-xs text-gray-500 mt-1">
            <span className="text-green-600 font-medium">{change}</span> from last month
          </p>
        )}
      </CardContent>
    </Card>
  );
};

const DashboardStats: React.FC = () => {
  const stats = [
    {
      title: 'Total Projects',
      value: '1,248',
      change: '+12.5%',
      icon: <FileText className="h-4 w-4 text-white" />,
      color: 'bg-blue-500'
    },
    {
      title: 'Active Procurements',
      value: '342',
      change: '+8.2%',
      icon: <TrendingUp className="h-4 w-4 text-white" />,
      color: 'bg-green-500'
    },
    {
      title: 'Pending Approvals',
      value: '89',
      change: '-3.1%',
      icon: <Clock className="h-4 w-4 text-white" />,
      color: 'bg-yellow-500'
    },
    {
      title: 'Completed This Month',
      value: '156',
      change: '+15.7%',
      icon: <CheckCircle className="h-4 w-4 text-white" />,
      color: 'bg-purple-500'
    },
    {
      title: 'Timeline Violations',
      value: '23',
      change: '-5.2%',
      icon: <AlertTriangle className="h-4 w-4 text-white" />,
      color: 'bg-red-500'
    },
    {
      title: 'Total Budget (NPR)',
      value: '2.4B',
      change: '+18.3%',
      icon: <DollarSign className="h-4 w-4 text-white" />,
      color: 'bg-indigo-500'
    }
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
      {stats.map((stat, index) => (
        <StatCard
          key={index}
          title={stat.title}
          value={stat.value}
          change={stat.change}
          icon={stat.icon}
          color={stat.color}
        />
      ))}
    </div>
  );
};

export default DashboardStats;