import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Calendar,
  Clock,
  AlertTriangle,
  CheckCircle,
  FileText,
  Users,
  Award,
  Hand
} from 'lucide-react';

interface TimelineStep {
  id: string;
  name: string;
  description: string;
  status: 'completed' | 'current' | 'upcoming' | 'delayed';
  date: string;
  deadline: string;
  icon: React.ReactNode;
  legalDays: number;
  actualDays: number;
}

const ProcurementTimeline: React.FC = () => {
  const timelineSteps: TimelineStep[] = [
    {
      id: 'step-1',
      name: 'Project Planning',
      description: 'Initial project identification and feasibility study',
      status: 'completed',
      date: '2081-01-15',
      deadline: '2081-01-30',
      icon: <FileText className="h-5 w-5" />,
      legalDays: 15,
      actualDays: 12
    },
    {
      id: 'step-2',
      name: 'Cost Estimate',
      description: 'Detailed cost estimation and budget allocation',
      status: 'completed',
      date: '2081-02-01',
      deadline: '2081-02-10',
      icon: <Award className="h-5 w-5" />,
      legalDays: 10,
      actualDays: 8
    },
    {
      id: 'step-3',
      name: 'Procurement Plan',
      description: 'Development of procurement plan and approval',
      status: 'completed',
      date: '2081-02-12',
      deadline: '2081-02-25',
      icon: <Calendar className="h-5 w-5" />,
      legalDays: 14,
      actualDays: 13
    },
    {
      id: 'step-4',
      name: 'Bid Preparation',
      description: 'Bid document preparation and advertisement',
      status: 'current',
      date: '2081-02-26',
      deadline: '2081-03-15',
      icon: <Users className="h-5 w-5" />,
      legalDays: 18,
      actualDays: 5
    },
    {
      id: 'step-5',
      name: 'Bid Submission',
      description: 'Bid submission period and evaluation',
      status: 'upcoming',
      date: '2081-03-16',
      deadline: '2081-04-05',
      icon: <Clock className="h-5 w-5" />,
      legalDays: 21,
      actualDays: 0
    },
    {
      id: 'step-6',
      name: 'Contract Award',
      description: 'Contract award and signing ceremony',
      status: 'upcoming',
      date: '2081-04-06',
      deadline: '2081-04-15',
      icon: <Hand className="h-5 w-5" />,
      legalDays: 10,
      actualDays: 0
    },
    {
      id: 'step-7',
      name: 'Work Execution',
      description: 'Project implementation and monitoring',
      status: 'upcoming',
      date: '2081-04-16',
      deadline: '2081-09-30',
      icon: <CheckCircle className="h-5 w-5" />,
      legalDays: 167,
      actualDays: 0
    }
  ];

  const getStatusColor = (status: TimelineStep['status']) => {
    switch (status) {
      case 'completed': return 'bg-green-100 text-green-800';
      case 'current': return 'bg-blue-100 text-blue-800';
      case 'upcoming': return 'bg-gray-100 text-gray-800';
      case 'delayed': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: TimelineStep['status']) => {
    switch (status) {
      case 'completed': return 'Completed';
      case 'current': return 'In Progress';
      case 'upcoming': return 'Upcoming';
      case 'delayed': return 'Delayed';
      default: return 'Pending';
    }
  };

  const getTimelinePosition = (index: number, total: number) => {
    return `${(index / (total - 1)) * 100}%`;
  };

  return (
    <Card className="border border-gray-200 shadow-sm">
      <CardHeader>
        <CardTitle className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <Calendar className="h-5 w-5 text-gray-600" />
            <span>Procurement Timeline</span>
          </div>
          <Badge variant="outline" className="text-sm font-normal">
            Project: PRJ-2024-001
          </Badge>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="relative">
          {/* Timeline line */}
          <div className="absolute left-0 right-0 top-8 h-1 bg-gray-200" />
          
          {/* Timeline steps */}
          <div className="relative flex justify-between">
            {timelineSteps.map((step, index) => (
              <div 
                key={step.id}
                className="relative flex flex-col items-center w-32"
                style={{ left: getTimelinePosition(index, timelineSteps.length) }}
              >
                {/* Timeline dot */}
                <div className={`relative z-10 w-8 h-8 rounded-full flex items-center justify-center mb-2 ${
                  step.status === 'completed' ? 'bg-green-500' :
                  step.status === 'current' ? 'bg-blue-500' :
                  step.status === 'delayed' ? 'bg-red-500' : 'bg-gray-300'
                }`}>
                  <div className="text-white">
                    {step.icon}
                  </div>
                </div>
                
                {/* Status badge */}
                <Badge className={`mb-2 ${getStatusColor(step.status)}`}>
                  {getStatusText(step.status)}
                </Badge>
                
                {/* Step info */}
                <div className="text-center">
                  <h4 className="font-medium text-gray-900 text-sm">{step.name}</h4>
                  <p className="text-xs text-gray-600 mt-1">{step.description}</p>
                  
                  <div className="mt-2 space-y-1">
                    <div className="flex justify-between text-xs">
                      <span className="text-gray-500">Start:</span>
                      <span className="font-medium">{step.date}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                      <span className="text-gray-500">Deadline:</span>
                      <span className="font-medium">{step.deadline}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                      <span className="text-gray-500">Days:</span>
                      <span className={`font-medium ${
                        step.actualDays > step.legalDays ? 'text-red-600' : 'text-green-600'
                      }`}>
                        {step.actualDays}/{step.legalDays}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Timeline summary */}
        <div className="mt-8 pt-6 border-t border-gray-200">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="p-4 bg-green-50 rounded-lg">
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium text-green-800">Completed Steps</h4>
                  <p className="text-2xl font-bold text-green-900 mt-1">3</p>
                </div>
                <CheckCircle className="h-8 w-8 text-green-600" />
              </div>
            </div>
            
            <div className="p-4 bg-blue-50 rounded-lg">
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium text-blue-800">Current Step</h4>
                  <p className="text-2xl font-bold text-blue-900 mt-1">Bid Preparation</p>
                </div>
                <Clock className="h-8 w-8 text-blue-600" />
              </div>
            </div>
            
            <div className="p-4 bg-gray-50 rounded-lg">
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium text-gray-800">Days Remaining</h4>
                  <p className="text-2xl font-bold text-gray-900 mt-1">13</p>
                </div>
                <AlertTriangle className="h-8 w-8 text-gray-600" />
              </div>
            </div>
          </div>
          
          <div className="mt-4 text-sm text-gray-600">
            <p>
              <span className="font-medium">Note:</span> This timeline follows Nepal Public Procurement Act 2063. 
              Any deviation from legal timelines requires approval from the concerned authority.
            </p>
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default ProcurementTimeline;