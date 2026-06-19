import pandas as pd

# 1. Chemin vers ton fichier original
# Si c'est un vrai Excel, utilise le nom .xlsx, sinon garde plan.csv
file_source = "plan.csv" 

print("Nettoyage en cours...")

try:
    # On essaie de le lire comme un Excel car le contenu semble binaire
    try:
        df = pd.read_excel(file_source)
    except:
        # Si ça échoue, on le lit en CSV avec détection de séparateur
        df = pd.read_csv(file_source, sep=None, engine='python', encoding='latin-1', on_bad_lines='skip')

    # 2. Extraction des colonnes (Compte et Libellé)
    # D'après tes essais précédents, ce sont les colonnes aux index 4 et 5
    plan_clean = pd.DataFrame()
    plan_clean['compte'] = df.iloc[:, 4].astype(str).str.strip()
    plan_clean['libelle'] = df.iloc[:, 5].astype(str).str.strip()

    # 3. Nettoyage strict
    # On ne garde que les lignes où le compte est un chiffre
    plan_clean = plan_clean[plan_clean['compte'].str.isdigit()]
    # On enlève les doublons et les lignes vides
    plan_clean = plan_clean.drop_duplicates(subset=['compte'])
    plan_clean = plan_clean[plan_clean['libelle'] != 'nan']

    # 4. Sauvegarde du fichier miroir (le fichier PROPRE)
    # C'est ce fichier que tu vas importer manuellement
    output_file = 'plan_pour_phpmyadmin.csv'
    plan_clean.to_csv(output_file, index=False, sep=';', encoding='utf-8-sig')

    print(f"Terminé ! {len(plan_clean)} comptes extraits dans '{output_file}'")
    print("Tu peux maintenant importer ce fichier manuellement dans phpMyAdmin.")

except Exception as e:
    print(f"Erreur : {e}")