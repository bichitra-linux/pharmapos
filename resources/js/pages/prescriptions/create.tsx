import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { prescriptionsService } from '@/services/prescriptions';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast';
import { ArrowLeft, Upload } from 'lucide-react';

export default function CreatePrescription() {
    const navigate = useNavigate();
    const { addToast } = useToast();
    const [customerId, setCustomerId] = useState('');
    const [doctorName, setDoctorName] = useState('');
    const [hospitalName, setHospitalName] = useState('');
    const [prescriptionDate, setPrescriptionDate] = useState(new Date().toISOString().split('T')[0]);
    const [diagnosis, setDiagnosis] = useState('');
    const [notes, setNotes] = useState('');
    const [file, setFile] = useState<File | null>(null);

    const mutation = useMutation({
        mutationFn: (formData: FormData) => prescriptionsService.create(formData),
        onSuccess: (res) => {
            addToast({ type: 'success', title: 'Prescription created' });
            navigate(`/prescriptions/${res.data.id}`);
        },
        onError: () => {
            addToast({ type: 'error', title: 'Failed to create prescription' });
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData();
        if (customerId) formData.append('customer_id', customerId);
        formData.append('doctor_name', doctorName);
        if (hospitalName) formData.append('hospital_name', hospitalName);
        formData.append('prescription_date', prescriptionDate);
        if (diagnosis) formData.append('diagnosis', diagnosis);
        if (notes) formData.append('notes', notes);
        if (file) formData.append('image', file);
        mutation.mutate(formData);
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <Button variant="ghost" onClick={() => navigate('/prescriptions')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                <h1 className="text-2xl font-bold">Add Prescription</h1>
            </div>

            <form onSubmit={handleSubmit}>
                <Card>
                    <CardHeader>
                        <CardTitle>Prescription Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <Input label="Customer ID" type="number" value={customerId} onChange={(e) => setCustomerId(e.target.value)} hint="Optional" />
                            <Input label="Doctor Name" value={doctorName} onChange={(e) => setDoctorName(e.target.value)} required />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <Input label="Hospital Name" value={hospitalName} onChange={(e) => setHospitalName(e.target.value)} />
                            <Input label="Date" type="date" value={prescriptionDate} onChange={(e) => setPrescriptionDate(e.target.value)} required />
                        </div>
                        <Input label="Diagnosis" value={diagnosis} onChange={(e) => setDiagnosis(e.target.value)} />
                        <Input label="Notes" value={notes} onChange={(e) => setNotes(e.target.value)} />
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-gray-700">Upload Image</label>
                            <div className="flex items-center gap-3">
                                <input
                                    type="file"
                                    accept="image/*,.pdf"
                                    onChange={(e) => setFile(e.target.files?.[0] || null)}
                                    className="text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="mt-6 flex justify-end gap-3">
                    <Button variant="outline" type="button" onClick={() => navigate('/prescriptions')}>
                        Cancel
                    </Button>
                    <Button type="submit" loading={mutation.isPending}>
                        <Upload className="mr-2 h-4 w-4" />
                        Create Prescription
                    </Button>
                </div>
            </form>
        </div>
    );
}
