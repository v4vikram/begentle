/**
 * Builds assets/pincodes/{first two digits}.json from India Post's "All India Pincode Directory".
 *
 *   node tools/build-pincodes.mjs path/to/pincode.csv
 *
 * Source: data.gov.in (Government Open Data License - India, attribution required).
 * Each shard maps a PIN to [city, WooCommerce state code], e.g. {"201301":["Gautam Buddha Nagar","UP"]}.
 * City = the PIN's district (the one with the most post offices when a PIN spans several).
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const csvPath = process.argv[2];
if (!csvPath) {
	console.error('Usage: node tools/build-pincodes.mjs path/to/pincode.csv');
	process.exit(1);
}

const outDir = path.join(path.dirname(fileURLToPath(import.meta.url)), '..', 'assets', 'pincodes');

// India Post state names -> WooCommerce state codes (i18n/states.php).
const STATES = {
	'Andaman and Nico.In.': 'AN',
	'Andaman and Nicobar Islands': 'AN',
	'Andhra Pradesh': 'AP',
	'Arunachal Pradesh': 'AR',
	Assam: 'AS',
	Bihar: 'BR',
	Chandigarh: 'CH',
	Chattisgarh: 'CT',
	Chhattisgarh: 'CT',
	'Dadra and Nagar Hav.': 'DH', // Merged with Daman and Diu in 2020.
	'Daman and Diu': 'DH',
	Delhi: 'DL',
	Goa: 'GA',
	Gujarat: 'GJ',
	'Himachal Pradesh': 'HP',
	Haryana: 'HR',
	Jharkhand: 'JH',
	'Jammu and Kashmir': 'JK',
	Karnataka: 'KA',
	Kerala: 'KL',
	Ladakh: 'LA',
	Lakshadweep: 'LD',
	Maharashtra: 'MH',
	Megalaya: 'ML',
	Meghalaya: 'ML',
	Manipur: 'MN',
	'Madhya Pradesh': 'MP',
	Mizoram: 'MZ',
	Nagaland: 'NL',
	Odisha: 'OD',
	Punjab: 'PB',
	Pondicherry: 'PY',
	Puducherry: 'PY',
	Rajasthan: 'RJ',
	Sikkim: 'SK',
	Telangana: 'TS',
	'Tamil Nadu': 'TN',
	Tripura: 'TR',
	'Uttar Pradesh': 'UP',
	Uttarakhand: 'UK',
	'West Bengal': 'WB',
};

// Districts that became Ladakh in 2019 (the directory predates it).
const LADAKH = new Set(['LEH', 'KARGIL']);

// Directory spellings -> the city name people actually write.
const CITY_FIX = {
	'MUMBAI SUBUEBAN': 'Mumbai',
	'MUMBAI SUBURBAN': 'Mumbai',
	'NEW DELHI': 'New Delhi',
};

/** Minimal RFC 4180 parser: quoted fields may contain commas and doubled quotes. */
function parseCsv(text) {
	const rows = [];
	let row = [];
	let field = '';
	let quoted = false;

	for (let i = 0; i < text.length; i++) {
		const ch = text[i];
		if (quoted) {
			if (ch === '"' && text[i + 1] === '"') {
				field += '"';
				i++;
			} else if (ch === '"') {
				quoted = false;
			} else {
				field += ch;
			}
		} else if (ch === '"') {
			quoted = true;
		} else if (ch === ',') {
			row.push(field);
			field = '';
		} else if (ch === '\n' || ch === '\r') {
			if (ch === '\r' && text[i + 1] === '\n') {
				i++;
			}
			row.push(field);
			rows.push(row);
			row = [];
			field = '';
		} else {
			field += ch;
		}
	}
	if (field || row.length) {
		row.push(field);
		rows.push(row);
	}
	return rows;
}

const titleCase = (s) =>
	s
		.toLowerCase()
		.replace(/\s+/g, ' ')
		.trim()
		.replace(/(^|[\s\-(./])([a-z])/g, (m, p, c) => p + c.toUpperCase());

function cityFor(district, stateCode) {
	const d = district.trim().toUpperCase();
	if (CITY_FIX[d]) {
		return CITY_FIX[d];
	}
	if (stateCode === 'DL') {
		return 'Delhi';
	}
	return titleCase(district);
}

const [header, ...rows] = parseCsv(fs.readFileSync(csvPath, 'utf8'));
const col = (name) => header.indexOf(name);
const iPin = col('Pincode');
const iDistrict = col('District');
const iState = col('StateName');

if ([iPin, iDistrict, iState].includes(-1)) {
	console.error('Unexpected header:', header.join(','));
	process.exit(1);
}

const counts = {};
const unknownStates = {};
let skipped = 0;

for (const r of rows) {
	const pin = (r[iPin] || '').trim();
	const district = (r[iDistrict] || '').trim();
	const stateName = (r[iState] || '').trim();
	let code = STATES[stateName];

	if (!/^[1-9]\d{5}$/.test(pin) || !district || district === 'NA') {
		skipped++;
		continue;
	}
	if (!code) {
		unknownStates[stateName] = (unknownStates[stateName] || 0) + 1;
		continue;
	}
	if (LADAKH.has(district.toUpperCase())) {
		code = 'LA';
	}

	const key = cityFor(district, code) + '|' + code;
	(counts[pin] ||= {})[key] = (counts[pin][key] || 0) + 1;
}

const shards = {};
for (const [pin, options] of Object.entries(counts)) {
	const [city, code] = Object.entries(options).sort((a, b) => b[1] - a[1])[0][0].split('|');
	(shards[pin.slice(0, 2)] ||= {})[pin] = [city, code];
}

fs.rmSync(outDir, { recursive: true, force: true });
fs.mkdirSync(outDir, { recursive: true });
let bytes = 0;
for (const [prefix, map] of Object.entries(shards)) {
	const sorted = Object.fromEntries(Object.entries(map).sort());
	const json = JSON.stringify(sorted);
	fs.writeFileSync(path.join(outDir, prefix + '.json'), json);
	bytes += json.length;
}

console.log(
	`PINs: ${Object.keys(counts).length} | shards: ${Object.keys(shards).length} | total ${(bytes / 1024).toFixed(0)} KB | skipped rows: ${skipped}`
);
if (Object.keys(unknownStates).length) {
	console.log('Unmapped state names (rows):', unknownStates);
}
