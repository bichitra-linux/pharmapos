export const NEPAL_PROVINCES = [
    { label: 'Koshi', value: 'Koshi' },
    { label: 'Madhesh', value: 'Madhesh' },
    { label: 'Bagmati', value: 'Bagmati' },
    { label: 'Gandaki', value: 'Gandaki' },
    { label: 'Lumbini', value: 'Lumbini' },
    { label: 'Karnali', value: 'Karnali' },
    { label: 'Sudurpashchim', value: 'Sudurpashchim' },
];

export const NEPAL_LOCAL_LEVELS: Record<string, { label: string; value: string }[]> = {
    Koshi: [
        { label: 'Birtamod Municipality', value: 'Birtamod' },
        { label: 'Damak Municipality', value: 'Damak' },
        { label: 'Dharan Sub-Metropolitan', value: 'Dharan' },
        { label: 'Itahari Sub-Metropolitan', value: 'Itahari' },
        { label: 'Mechinagar Municipality', value: 'Mechinagar' },
    ],
    Madhesh: [
        { label: 'Janakpur Sub-Metropolitan', value: 'Janakpur' },
        { label: 'Birgunj Metropolitan', value: 'Birgunj' },
        { label: 'Kalaiya Sub-Metropolitan', value: 'Kalaiya' },
        { label: 'Jaleshwar Municipality', value: 'Jaleshwar' },
    ],
    Bagmati: [
        { label: 'Kathmandu Metropolitan', value: 'Kathmandu' },
        { label: 'Lalitpur Metropolitan', value: 'Lalitpur' },
        { label: 'Bharatpur Metropolitan', value: 'Bharatpur' },
        { label: 'Hetauda Sub-Metropolitan', value: 'Hetauda' },
        { label: 'Madhyapur Thimi Municipality', value: 'Madhyapur Thimi' },
        { label: 'Bhaktapur Municipality', value: 'Bhaktapur' },
    ],
    Gandaki: [
        { label: 'Pokhara Metropolitan', value: 'Pokhara' },
        { label: 'Waling Municipality', value: 'Waling' },
        { label: 'Gorkha Municipality', value: 'Gorkha' },
        { label: 'Damauli Municipality', value: 'Damauli' },
    ],
    Lumbini: [
        { label: 'Butwal Sub-Metropolitan', value: 'Butwal' },
        { label: 'Siddharthanagar Municipality', value: 'Siddharthanagar' },
        { label: 'Nepalgunj Sub-Metropolitan', value: 'Nepalgunj' },
        { label: 'Tansen Municipality', value: 'Tansen' },
    ],
    Karnali: [
        { label: 'Birendranagar Municipality', value: 'Birendranagar' },
        { label: 'Chandannath Municipality', value: 'Chandannath' },
    ],
    Sudurpashchim: [
        { label: 'Dhangadhi Sub-Metropolitan', value: 'Dhangadhi' },
        { label: 'Mahendranagar Municipality', value: 'Mahendranagar' },
        { label: 'Bhimdatta Municipality', value: 'Bhimdatta' },
    ],
};

export const COUNTRY_CODES = [
    { label: '+977 (Nepal)', value: '+977' },
    { label: '+91 (India)', value: '+91' },
    { label: '+1 (USA)', value: '+1' },
    { label: '+44 (UK)', value: '+44' },
    { label: '+86 (China)', value: '+86' },
];
