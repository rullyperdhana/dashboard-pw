import pandas as pd
import json

df = pd.read_excel('Daftar_SKPD_UPTD_2026.xls')

# Columns: Nomor, SKPD, SUB SKPD, Level
# Level might be 'SKPD' or 'SUB SKPD'

data = []
current_skpd_idx = 0
current_sub_skpd_idx = 0

for index, row in df.iterrows():
    if pd.isna(row['SKPD']) and pd.isna(row['SUB SKPD']):
        continue
        
    is_skpd = row['Level'] == 'SKPD'
    
    if is_skpd:
        current_skpd_idx += 1
        current_sub_skpd_idx = 0 # reset
        kode_skpd = f"1.{current_skpd_idx:02d}"
        nama_skpd = str(row['SKPD']).strip()
    else:
        current_sub_skpd_idx += 1
        kode_skpd = f"1.{current_skpd_idx:02d}.{current_sub_skpd_idx:02d}"
        nama_skpd = str(row['SUB SKPD']).strip()
        
    data.append({
        'nomor': row['Nomor'],
        'kode_skpd': kode_skpd,
        'nama_skpd': nama_skpd,
        'is_skpd': True if is_skpd else False
    })

with open('skpd_2026_parsed.json', 'w') as f:
    json.dump(data, f, indent=2)

print(f"Parsed {len(data)} rows.")
print("Sample:")
for d in data[:5]:
    print(d)
