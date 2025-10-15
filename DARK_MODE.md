# Configuration Dark Mode - Mini RSN

## Comment ça fonctionne ?

Le dark mode est géré par **Tailwind CSS** avec la stratégie `class`.

### Principe

1. **Configuration Tailwind** (`templates/base.html.twig`) :
   ```javascript
   tailwind.config = {
       darkMode: 'class'
   }
   ```
   Cette config dit à Tailwind : "Active les styles `dark:*` uniquement si la classe `dark` est présente sur `<html>`"

2. **JavaScript simple** (sur chaque page) :
   ```javascript
   document.getElementById('darkModeToggle').addEventListener('click', () => {
       document.documentElement.classList.toggle('dark');
   });
   ```
   - `document.documentElement` = élément `<html>`
   - `.classList.toggle('dark')` = Ajoute la classe `dark` si elle n'existe pas, la retire si elle existe

3. **Pas de localStorage** : Le dark mode ne se sauvegarde PAS entre les pages. C'est voulu !

---

## Utilisation dans les templates

### Classes Tailwind pour le dark mode

Tailwind applique automatiquement les styles préfixés par `dark:` quand la classe `dark` est sur `<html>`.

**Exemples :**

```html
<!-- Fond blanc en mode clair, gris foncé en mode sombre -->
<div class="bg-white dark:bg-gray-800">
    Contenu
</div>

<!-- Texte noir en mode clair, blanc en mode sombre -->
<p class="text-gray-900 dark:text-white">
    Mon texte
</p>

<!-- Bordure grise en mode clair, grise foncée en mode sombre -->
<input class="border-gray-300 dark:border-gray-600">
```

### Structure du bouton Dark Mode

Le bouton utilise deux icônes SVG qui s'affichent conditionnellement :

```html
<button id="darkModeToggle" class="...">
    <!-- Icône soleil : cachée par défaut, visible en dark mode -->
    <svg id="sunIcon" class="hidden dark:block">
        <!-- SVG du soleil -->
    </svg>

    <!-- Icône lune : visible par défaut, cachée en dark mode -->
    <svg id="moonIcon" class="block dark:hidden">
        <!-- SVG de la lune -->
    </svg>
</button>
```

**Logique :**
- **Mode clair** (pas de classe `dark`) :
  - Icône soleil : `hidden` (cachée)
  - Icône lune : `block` (visible) → On voit la lune

- **Mode sombre** (classe `dark` présente) :
  - Icône soleil : `hidden dark:block` → devient `block` (visible) → On voit le soleil
  - Icône lune : `block dark:hidden` → devient `hidden` (cachée)

---

## Pages concernées

Le dark mode est implémenté sur :

1. ✅ **Page de connexion** (`templates/security/login.html.twig`)
2. ✅ **Page d'inscription** (`templates/registration/register.html.twig`)
3. ✅ **Page d'accueil** (`templates/home/index.html.twig`)

Chaque page a :
- Un bouton `#darkModeToggle`
- Le même script JavaScript (3 lignes)
- Les classes `dark:*` sur les éléments

---

## Ajouter le dark mode à une nouvelle page

### Étape 1 : Ajouter le bouton

```html
<button id="darkModeToggle"
        class="p-3 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-yellow-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all">
    <svg id="sunIcon" class="w-6 h-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
    </svg>
    <svg id="moonIcon" class="w-6 h-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
    </svg>
</button>
```

### Étape 2 : Ajouter le script

À la fin du template, avant `{% endblock %}` :

```html
<script>
    document.getElementById('darkModeToggle').addEventListener('click', () => {
        document.documentElement.classList.toggle('dark');
    });
</script>
```

### Étape 3 : Ajouter les classes dark: aux éléments

Sur chaque élément qui doit changer en mode sombre, ajoutez les classes `dark:*` :

```html
<!-- Exemples -->
<div class="bg-white dark:bg-gray-800">...</div>
<h1 class="text-gray-900 dark:text-white">...</h1>
<input class="bg-white dark:bg-gray-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600">
```

---

## Classes Tailwind courantes pour le dark mode

### Arrière-plans

| Mode clair | Mode sombre | Usage |
|------------|-------------|-------|
| `bg-white` | `dark:bg-gray-800` | Cartes, conteneurs |
| `bg-gray-50` | `dark:bg-gray-900` | Fond de page |
| `bg-gray-100` | `dark:bg-gray-800` | Zones secondaires |
| `bg-gray-200` | `dark:bg-gray-700` | Boutons, inputs |

### Textes

| Mode clair | Mode sombre | Usage |
|------------|-------------|-------|
| `text-gray-900` | `dark:text-white` | Titres, texte principal |
| `text-gray-600` | `dark:text-gray-300` | Texte secondaire |
| `text-gray-500` | `dark:text-gray-400` | Texte tertiaire, placeholders |

### Bordures

| Mode clair | Mode sombre | Usage |
|------------|-------------|-------|
| `border-gray-300` | `dark:border-gray-600` | Bordures d'inputs |
| `border-gray-200` | `dark:border-gray-700` | Bordures de cartes |

### Messages (success, error, info)

| Type | Mode clair | Mode sombre |
|------|------------|-------------|
| Succès (fond) | `bg-green-50` | `dark:bg-green-900/20` |
| Succès (bordure) | `border-green-200` | `dark:border-green-800` |
| Succès (texte) | `text-green-800` | `dark:text-green-200` |
| Erreur (fond) | `bg-red-50` | `dark:bg-red-900/20` |
| Erreur (bordure) | `border-red-200` | `dark:border-red-800` |
| Erreur (texte) | `text-red-800` | `dark:text-red-200` |

---

## Pourquoi pas de localStorage ?

**Choix de conception :** Le dark mode ne persiste pas entre les pages/rechargements.

**Raison :** Simplicité ! Pas besoin de gérer :
- Le stockage local
- L'initialisation au chargement
- Les préférences système
- La synchronisation entre onglets

**Avantage :**
- Code ultra simple (3 lignes de JS)
- Pas de bug de synchronisation
- Comportement prévisible

**Si vous voulez ajouter la persistance plus tard :**

```javascript
// Version avec localStorage (3 lignes de plus)
const toggle = document.getElementById('darkModeToggle');
const html = document.documentElement;

// Charger la préférence
if (localStorage.getItem('theme') === 'dark') {
    html.classList.add('dark');
}

// Toggle et sauvegarder
toggle.addEventListener('click', () => {
    html.classList.toggle('dark');
    localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
});
```

---

## Transitions

Les transitions rendent le changement de mode fluide.

**Sur le body** (`templates/base.html.twig`) :

```html
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
```

**Sur les éléments :**

```html
<div class="bg-white dark:bg-gray-800 transition-colors duration-200">
    ...
</div>
```

**Classes Tailwind :**
- `transition-colors` : Anime uniquement les changements de couleur
- `duration-200` : Animation de 200ms (rapide et fluide)

---

## Ressources

- [Tailwind Dark Mode Documentation](https://tailwindcss.com/docs/dark-mode)
- [Tailwind Color Palette](https://tailwindcss.com/docs/customizing-colors)

---

## Résumé

✅ **Configuration** : `darkMode: 'class'` dans Tailwind config
✅ **Toggle** : Bouton qui ajoute/retire la classe `dark` sur `<html>`
✅ **Styles** : Classes `dark:*` appliquées automatiquement par Tailwind
✅ **Simple** : 3 lignes de JavaScript, pas de localStorage
✅ **Réactif** : Transitions fluides avec `transition-colors`
