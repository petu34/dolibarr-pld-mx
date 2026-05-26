Armonización de information tracking
proyecto custom dolibarr-pld-mx

# Lo que necesitas hacer (3 opciones)
## Opción A — Fusionar main → develop (recomendado, porque develop es default)
git checkout develop
git pull origin develop
git merge main
### Resolver conflictos si los hay (probablemente el commit duplicado ee377f5/5bd731a)
git push origin develop
## Opción B — Fusionar develop → main (si quieres que main sea default otra vez)
git checkout main
git merge origin/develop
git push origin main
# Luego en GitHub: Settings → Default branch → cambiar a main
## Opción C — Cherry-pick solo lo que falta (más limpio, más trabajo)
# Los commits de main que faltan en develop ya son solo merges de PRs viejos.
# Si esos PRs ya están cerrados, develop ya tiene ese código por otra vía.
# Recomendado: comparar con git diff develop..main para ver si realmente hay
# cambios de código que falten en develop.
---
Mi recomendación: Opción A. develop es la rama default en GitHub y ya tiene el trabajo más reciente del módulo PLD. Solo necesitas poner a develop al día con los merges que se hicieron directo a main.