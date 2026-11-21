# Byte — design system

**Direção:** acadêmico sóbrio. Menu lateral em azul-marinho, conteúdo em cinza-azulado de papel, ação em azul-tinta, verde para progresso/acerto, dourado como destaque raro.

## Tokens (em `resources/css/app.css`)
| Papel | Token | Valor |
|---|---|---|
| Ação / marca | `brand-600` / `brand-700` | `#2150a8` / `#183f87` |
| Shell (menu) | `shell` / `shell-hover` / `shell-text` | `#12264a` / `#1c3766` / `#c9d6ee` |
| Fundo | `canvas` | `#f3f5f9` |
| Borda | `line` | `#dce2ed` |
| Texto | `ink` / `muted` | `#14213d` / `#4a5568` |
| Sucesso | `success` | `#0f7b4d` |
| Destaque | `gold` | `#b4690e` |
| Disciplinas | — | `#2150a8 #0f7b4d #b4690e #0e7490` |

## Tipografia
Títulos: Poppins 600. Texto: Open Sans. Números de painel: `tabular-nums`.

## Regras
- Ícones: Phosphor (`@phosphor-icons/vue`), nunca emoji.
- Elevação só por borda; raio de card 12px; alvos de toque ≥ 44px.
- Sem barras coloridas na lateral de cards; cor de disciplina aparece como ponto.
- Texto ≥ 4.5:1; foco visível; respeitar `prefers-reduced-motion`.
