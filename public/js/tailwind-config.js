  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
            "primary": "rgb(var(--c-primary) / <alpha-value>)",
            "accent-emerald": "rgb(var(--c-accent-emerald) / <alpha-value>)",
            "accent-lime": "rgb(var(--c-accent-lime) / <alpha-value>)",
            "surface-obsidian": "rgb(var(--c-surface-obsidian) / <alpha-value>)",
            "surface-carbon": "rgb(var(--c-surface-carbon) / <alpha-value>)",
            "surface-slate": "rgb(var(--c-surface-slate) / <alpha-value>)",
            "surface-elevated": "rgb(var(--c-surface-elevated) / <alpha-value>)",
            "text-crisp": "rgb(var(--c-text-crisp) / <alpha-value>)",
            "text-muted": "rgb(var(--c-text-muted) / <alpha-value>)",
            "primary-glow": "var(--primary-glow)",
            "border-glass": "var(--border-glass)",
            "border-accent": "var(--border-accent)"
  },
        fontFamily: {
          sans: ['var(--font-body)'],
          display: ['var(--font-display)']
        }
      }
    }
  }
