# Projet Correction Automatique - PoC

Plateforme web pour :
- Connexion étudiant
- Dépôt d'exercices (Python/C)
- Stockage des soumissions
- Interface de suivi

⚠️ Scripts de correction automatisée seront ajoutés plus tard.
---

### 5️⃣ Initialise ton dépôt Git local et pousse vers GitHub
git init
git add .
git commit -m "Version initiale du site étudiant"
git remote add origin https://github.com/TON_UTILISATEUR/infra-poc-correction.git
git branch -M main
git push -u origin main
---

## ✅ Résultat : sur GitHub

Sur ton dépôt, tu dois voir :
public/
→ Tous tes fichiers PHP
.gitignore
README.md
Et le dossier `uploads/` ne sera **pas du tout versionné 👍**

---

👉 Tu pourras plus tard ajouter :
- un dossier `correction-scripts/` ou `workers/` pour tes scripts Python / bash de correction
- une base de référence (ex: `reference_outputs/`)
- un fichier `.env.example` pour montrer la config

---

## 🔒 Conseils si tu travailles à plusieurs :

Tu pourras plus tard ajouter :
- un petit fichier `config.php` (non tracké) pour centraliser les accès BDD
- des clés d’API ou Jeton CSRF (si évolutions prévues)
- des branches pour staging / production

---