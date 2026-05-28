import { useNavigate } from 'react-router-dom';
import { Card, CardContent } from '@/components/ui/card';
import {
    BarChart3,
    Package,
    AlertTriangle,
    TrendingUp,
    FileText,
    Shield,
} from 'lucide-react';

const reportLinks = [
    { to: '/reports/sales', icon: BarChart3, label: 'Sales Report', description: 'Daily, weekly, monthly sales analysis' },
    { to: '/reports/inventory', icon: Package, label: 'Inventory Report', description: 'Current stock levels and valuation' },
    { to: '/reports/expiry', icon: AlertTriangle, label: 'Expiry Report', description: 'Medicines expiring soon' },
    { to: '/reports/profit-loss', icon: TrendingUp, label: 'Profit & Loss', description: 'Revenue, costs, and profit analysis' },
    { to: '/reports/vat', icon: FileText, label: 'VAT Report', description: 'Tax collected and payable' },
    { to: '/reports/narcotics', icon: Shield, label: 'Narcotics Register', description: 'Controlled substance records' },
];

export default function ReportsIndex() {
    const navigate = useNavigate();

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold">Reports</h1>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {reportLinks.map((report) => (
                    <Card
                        key={report.to}
                        className="cursor-pointer transition-shadow hover:shadow-md"
                        onClick={() => navigate(report.to)}
                    >
                        <CardContent className="flex items-start gap-4 p-6">
                            <div className="rounded-lg bg-primary-50 p-3">
                                <report.icon className="h-6 w-6 text-primary-600" />
                            </div>
                            <div>
                                <h3 className="font-semibold">{report.label}</h3>
                                <p className="text-sm text-gray-500">{report.description}</p>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}
