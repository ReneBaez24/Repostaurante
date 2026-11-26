"""Gráficas de la medición de rendimiento (opcional: requiere Python 3 + matplotlib).

    python tools/rendimiento/graficas.py

Lee docs/rendimiento/resultados.csv y memoria-antes/despues.csv (los genera `php artisan rendimiento:medir`)
y crea tiempos.png y memoria.png en la misma carpeta.
"""
import csv
import statistics
from pathlib import Path

import matplotlib

matplotlib.use("Agg")
import matplotlib.pyplot as plt

CARPETA = Path(__file__).resolve().parents[2] / "docs" / "rendimiento"
TINTA, SUAVE, ANTES, DESPUES = "#2b2b2b", "#8a8a8a", "#b5651d", "#2f6f8f"
plt.rcParams.update({"font.size": 11, "axes.spines.top": False, "axes.spines.right": False,
                     "axes.edgecolor": SUAVE, "axes.labelcolor": TINTA, "xtick.color": TINTA, "ytick.color": TINTA})

# --- Tiempo por versión ---
filas = {f["version"]: f for f in csv.DictReader(open(CARPETA / "resultados.csv"))}
versiones = [("antes", "Antes\n(secuencial, sin caché)"), ("despues", "Después\n(paralelo, campos, caché, lotes)")]
tiempos = [float(filas[v]["segundos"]) for v, _ in versiones]

fig, ax = plt.subplots(figsize=(7.5, 4))
barras = ax.bar([t for _, t in versiones], tiempos, color=[ANTES, DESPUES], width=0.5)
for barra, t in zip(barras, tiempos):
    ax.text(barra.get_x() + barra.get_width() / 2, t + max(tiempos) * 0.02, f"{t:.2f} s", ha="center", color=TINTA, fontweight="bold")
ax.set_ylabel("Segundos")
ax.set_ylim(top=max(tiempos) * 1.15)
lineas = (CARPETA.parents[1] / "tools/rendimiento/codigos.txt").read_text(encoding="utf-8").splitlines()
codigos = sum(1 for l in lineas if l.strip() and not l.startswith("#"))
ax.set_title(f"Importar {codigos} códigos: de {tiempos[0]:.1f} s a {tiempos[1]:.1f} s ({tiempos[0] / tiempos[1]:.1f}× más rápido)", loc="left", color=TINTA)
fig.tight_layout()
fig.savefig(CARPETA / "tiempos.png", dpi=150)

# --- Memoria a lo largo del tiempo (como `mprof plot`) ---
fig, ax = plt.subplots(figsize=(7.5, 4))
for nombre, color, titulo in [("antes", ANTES, "Antes"), ("despues", DESPUES, "Después")]:
    datos = list(csv.DictReader(open(CARPETA / f"memoria-{nombre}.csv")))
    ax.plot([float(d["segundos"]) for d in datos], [float(d["memoria_mb"]) for d in datos], color=color, linewidth=2, label=titulo)
ax.set_xlabel("Segundos desde el inicio de la importación")
ax.set_ylabel("Memoria en uso (MB)")
ax.set_ylim(bottom=0, top=36)
ax.legend(frameon=False)
ax.set_title("La memoria se mantiene igual; el proceso termina mucho antes", loc="left", color=TINTA)
fig.tight_layout()
fig.savefig(CARPETA / "memoria.png", dpi=150)
print("Gráficas guardadas en", CARPETA)
