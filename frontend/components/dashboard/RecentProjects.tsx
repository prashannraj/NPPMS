import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
  Calendar, 
  MapPin, 
  Users,
  TrendingUp,
  Clock,
  CheckCircle
} from 'lucide-react';

interface Project {
  id: string;
  name: string;
  location: string;
  budget: string;
  status: 'planning' | 'bidding' | 'execution' | 'completion' | 'handover';
  progress: number;
  startDate: string;
  endDate: string;
  procurementMethod: string;
  timelineStatus: 'on-track' | 'at-risk' | 'delayed';
}

const RecentProjects: React.FC = () => {
  const projects: Project[] = [
    {
      id: 'PRJ-2024-001',
      name: 'Municipal Road Construction - Ward 5',
      location: 'Kathmandu Metropolitan City',
      budget: 'NPR 45,000,000',
      status: 'execution',
      progress: 65,
      startDate: '2081-01-15',
      endDate: '2081-06-30',
      procurementMethod: 'Open Competitive Bidding',
      timelineStatus: 'on-track'
    },
    {
      id: 'PRJ-2024-002',
      name: 'School Building Renovation',
      location: 'Lalitpur Metropolitan City',
      budget: 'NPR 28,500,000',
      status: 'bidding',
      progress: 30,
      startDate: '2081-02-01',
      endDate: '2081-05-15',
      procurementMethod: 'Limited Bidding',
      timelineStatus: 'at-risk'
    },
    {
      id: 'PRJ-2024-003',
      name: 'Water Supply System Upgrade',
      location: 'Bhaktapur Municipality',
      budget: 'NPR 92,000,000',
      status: 'planning',
      progress: 15,
      startDate: '2081-03-10',
      endDate: '2081-09-30',
      procurementMethod: 'Open Competitive Bidding',
      timelineStatus: 'on-track'
    },
    {
      id: 'PRJ-2024-004',
      name: 'Health Post Construction',
      location: 'Kirtipur Municipality',
      budget: 'NPR 18,750,000',
      status: 'completion',
      progress: 90,
      startDate: '2080-11-20',
      endDate: '2081-04-30',
      procurementMethod: 'Direct Contracting',
      timelineStatus: 'delayed'
    },
    {
      id: 'PRJ-2024-005',
      name: 'Sewage Treatment Plant',
      location: 'Madhyapur Thimi Municipality',
      budget: 'NPR 120,000,000',
      status: 'handover',
      progress: 100,
      startDate: '2080-09-01',
      endDate: '2081-03-31',
      procurementMethod: 'Open Competitive Bidding',
      timelineStatus: 'on-track'
    }
  ];

  const getStatusColor = (status: Project['status']) => {
    switch (status) {
      case 'planning': return 'bg-blue-100 text-blue-800';
      case 'bidding': return 'bg-yellow-100 text-yellow-800';
      case 'execution': return 'bg-green-100 text-green-800';
      case 'completion': return 'bg-purple-100 text-purple-800';
      case 'handover': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusIcon = (status: Project['status']) => {
    switch (status) {
      case 'planning': return <Clock className="h-4 w-4" />;
      case 'bidding': return <Users className="h-4 w-4" />;
      case 'execution': return <TrendingUp className="h-4 w-4" />;
      case 'completion': return <CheckCircle className="h-4 w-4" />;
      case 'handover': return <CheckCircle className="h-4 w-4" />;
      default: return <Clock className="h-4 w-4" />;
    }
  };

  const getTimelineStatusColor = (status: Project['timelineStatus']) => {
    switch (status) {
      case 'on-track': return 'bg-green-100 text-green-800';
      case 'at-risk': return 'bg-yellow-100 text-yellow-800';
      case 'delayed': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <Card className="border border-gray-200 shadow-sm">
      <CardHeader>
        <CardTitle className="flex items-center justify-between">
          <span>Recent Projects</span>
          <Badge variant="outline" className="text-sm font-normal">
            Last 30 days
          </Badge>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {projects.map((project) => (
            <div 
              key={project.id}
              className="flex items-center justify-between p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <div className="flex-1">
                <div className="flex items-center space-x-3">
                  <div className={`p-2 rounded-full ${getStatusColor(project.status)}`}>
                    {getStatusIcon(project.status)}
                  </div>
                  <div>
                    <h4 className="font-medium text-gray-900">{project.name}</h4>
                    <div className="flex items-center space-x-4 mt-1 text-sm text-gray-600">
                      <span className="flex items-center">
                        <MapPin className="h-3 w-3 mr-1" />
                        {project.location}
                      </span>
                      <span className="flex items-center">
                        <Calendar className="h-3 w-3 mr-1" />
                        {project.startDate} - {project.endDate}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="flex items-center space-x-6">
                <div className="text-right">
                  <div className="font-semibold text-gray-900">{project.budget}</div>
                  <div className="text-xs text-gray-500">{project.procurementMethod}</div>
                </div>
                
                <div className="w-32">
                  <div className="flex justify-between text-sm mb-1">
                    <span className="text-gray-600">Progress</span>
                    <span className="font-medium">{project.progress}%</span>
                  </div>
                  <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                      className="h-full bg-green-500 rounded-full"
                      style={{ width: `${project.progress}%` }}
                    />
                  </div>
                </div>
                
                <Badge className={getTimelineStatusColor(project.timelineStatus)}>
                  {project.timelineStatus.replace('-', ' ')}
                </Badge>
              </div>
            </div>
          ))}
        </div>
        
        <div className="mt-6 pt-4 border-t border-gray-200">
          <button className="w-full py-2 text-center text-sm font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition-colors">
            View All Projects →
          </button>
        </div>
      </CardContent>
    </Card>
  );
};

export default RecentProjects;