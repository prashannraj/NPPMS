import React from 'react';
import { 
  Building2, 
  Shield, 
  Globe,
  Mail,
  Phone,
  MapPin
} from 'lucide-react';

const Footer: React.FC = () => {
  return (
    <footer className="mt-auto border-t border-gray-200 bg-gray-50">
      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          {/* Brand and Description */}
          <div className="space-y-4">
            <div className="flex items-center space-x-2">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600">
                <Building2 className="h-6 w-6 text-white" />
              </div>
              <div>
                <h2 className="text-xl font-bold text-gray-900">NPPMS</h2>
                <p className="text-sm text-gray-600">Nepal Public Procurement Management System</p>
              </div>
            </div>
            <p className="text-sm text-gray-600">
              A comprehensive digital platform for managing public procurement processes 
              across Provincial and Local Governments of Nepal.
            </p>
            <div className="flex items-center space-x-4">
              <Shield className="h-5 w-5 text-green-600" />
              <span className="text-sm text-gray-600">ISO 27001 Certified</span>
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h3 className="mb-4 text-lg font-semibold text-gray-900">Quick Links</h3>
            <ul className="space-y-2">
              <li>
                <a href="/dashboard" className="text-sm text-gray-600 hover:text-blue-600">
                  Dashboard
                </a>
              </li>
              <li>
                <a href="/projects" className="text-sm text-gray-600 hover:text-blue-600">
                  Projects
                </a>
              </li>
              <li>
                <a href="/procurement" className="text-sm text-gray-600 hover:text-blue-600">
                  Procurement Plans
                </a>
              </li>
              <li>
                <a href="/tenders" className="text-sm text-gray-600 hover:text-blue-600">
                  Tender Notices
                </a>
              </li>
              <li>
                <a href="/contracts" className="text-sm text-gray-600 hover:text-blue-600">
                  Contract Management
                </a>
              </li>
              <li>
                <a href="/reports" className="text-sm text-gray-600 hover:text-blue-600">
                  Reports & Analytics
                </a>
              </li>
            </ul>
          </div>

          {/* Legal & Compliance */}
          <div>
            <h3 className="mb-4 text-lg font-semibold text-gray-900">Legal & Compliance</h3>
            <ul className="space-y-2">
              <li>
                <a href="/public-procurement-act" className="text-sm text-gray-600 hover:text-blue-600">
                  Public Procurement Act, 2063
                </a>
              </li>
              <li>
                <a href="/regulation" className="text-sm text-gray-600 hover:text-blue-600">
                  Public Procurement Regulation, 2064
                </a>
              </li>
              <li>
                <a href="/guidelines" className="text-sm text-gray-600 hover:text-blue-600">
                  Procurement Guidelines
                </a>
              </li>
              <li>
                <a href="/blacklist" className="text-sm text-gray-600 hover:text-blue-600">
                  Blacklisted Contractors
                </a>
              </li>
              <li>
                <a href="/privacy" className="text-sm text-gray-600 hover:text-blue-600">
                  Privacy Policy
                </a>
              </li>
              <li>
                <a href="/terms" className="text-sm text-gray-600 hover:text-blue-600">
                  Terms of Service
                </a>
              </li>
            </ul>
          </div>

          {/* Contact Information */}
          <div>
            <h3 className="mb-4 text-lg font-semibold text-gray-900">Contact Us</h3>
            <ul className="space-y-3">
              <li className="flex items-start space-x-3">
                <MapPin className="h-5 w-5 text-gray-400 mt-0.5" />
                <div>
                  <p className="text-sm text-gray-600">
                    Ministry of Federal Affairs and General Administration
                  </p>
                  <p className="text-sm text-gray-600">
                    Singha Durbar, Kathmandu, Nepal
                  </p>
                </div>
              </li>
              <li className="flex items-center space-x-3">
                <Phone className="h-5 w-5 text-gray-400" />
                <span className="text-sm text-gray-600">+977-1-4211000</span>
              </li>
              <li className="flex items-center space-x-3">
                <Mail className="h-5 w-5 text-gray-400" />
                <span className="text-sm text-gray-600">support@nppms.gov.np</span>
              </li>
              <li className="flex items-center space-x-3">
                <Globe className="h-5 w-5 text-gray-400" />
                <span className="text-sm text-gray-600">www.nppms.gov.np</span>
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="mt-8 border-t border-gray-300 pt-6">
          <div className="flex flex-col md:flex-row items-center justify-between">
            <div className="text-sm text-gray-600">
              © 2081 Nepal Public Procurement Management System. All rights reserved.
            </div>
            <div className="mt-4 md:mt-0 flex items-center space-x-6">
              <span className="text-sm text-gray-600">
                Version: 2.1.0 | Last Updated: 2081-01-15
              </span>
              <div className="flex items-center space-x-4">
                <a href="#" className="text-sm text-gray-600 hover:text-blue-600">
                  Help Center
                </a>
                <a href="#" className="text-sm text-gray-600 hover:text-blue-600">
                  Documentation
                </a>
                <a href="#" className="text-sm text-gray-600 hover:text-blue-600">
                  API
                </a>
              </div>
            </div>
          </div>
          <div className="mt-4 text-center text-xs text-gray-500">
            <p>
              This system is developed in compliance with the Public Procurement Act of Nepal 
              and follows the Government of Nepal's Digital Nepal Framework.
            </p>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;