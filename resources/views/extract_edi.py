import pandas as pd
import re

# Chemin vers ton fichier
excel_file = "Codification des cellules modèle Normal.xlsx"

# Charger toutes les feuilles
xls = pd.ExcelFile(excel_file)
all_data = []

print("Extraction en cours...")

for sheet in xls.sheet_names:
    # Lire la feuille
    df = pd.read_excel(excel_file, sheet_name=sheet)
    
    for index, row in df.iterrows():
        # Le libellé est souvent dans la première colonne textuelle
        libelle = str(row.iloc[0]).strip()
        
        for cell_value in row:
            val_str = str(cell_value)
            # On cherche le motif "(code = XXX)"
            match = re.search(r'\(code\s*=\s*(\d+)\)', val_str)
            
            if match:
                code_edi = match.group(1)
                
                # Détection simplifiée des labels de colonnes
                col1, col2, col3 = "Valeur 1", "Valeur 2", "Valeur 3"
                if "actif" in sheet.lower():
                    col1, col2, col3 = "Brut", "Amortissement", "Net"
                elif "cpc" in sheet.lower():
                    col1, col2, col3 = "Exercice N", "Exercice N-1", ""

                all_data.append({
                    'code_edi': code_edi,
                    'tableau_nom': sheet,
                    'libelle_ligne': libelle,
                    'label_col1': col1,
                    'label_col2': col2,
                    'label_col3': col3
                })

# Convertir en DataFrame et sauvegarder en CSV pour PHPMyAdmin
df_result = pd.DataFrame(all_data).drop_duplicates(subset=['code_edi'])
df_result.to_csv('import_edi.csv', index=False, sep=';', encoding='utf-8-sig')

print(f"Terminé ! {len(df_result)} codes extraits dans 'import_edi.csv'")