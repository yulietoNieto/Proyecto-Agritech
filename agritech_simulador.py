"""
AGRITECH — Simulador IoT integrado con API Laravel
====================================================
Autor: Yulieth Sthephania Santana Nieto
Universidad de Cundinamarca - Carmen de Carupa
====================================================
Reemplaza el script original: mantiene la misma lógica
de sensores pero ahora ENVÍA los datos a la API Laravel
y también genera el PDF con los datos reales recibidos.

Requisitos:
    pip install requests reportlab matplotlib
"""

import random
import statistics
import time
import os
import requests
import matplotlib.pyplot as plt
from reportlab.lib.pagesizes import letter
from reportlab.platypus import (SimpleDocTemplate, Paragraph, Spacer,
                                Image, Table, PageBreak)
from reportlab.lib.styles import getSampleStyleSheet
from reportlab.platypus import TableStyle
from reportlab.lib import colors

# ============================================================
# CONFIGURACIÓN — ajusta solo esta sección
# ============================================================

API_URL    = "http://localhost:8000/api"   # URL de tu Laravel
EMAIL      = "admin@agritech.co"           # usuario registrado
PASSWORD   = "Agritech2024!"              # contraseña

# IDs de sensores (ver en DB: SELECT id,type FROM sensors)
# El seeder los crea en este orden: humidity=1, temperature=2, nutrients=3
SENSOR_IDS = {
    "humedad":      1,
    "temperatura":  2,
    "nutrientes":   3,
}

INTERVALO_SEGUNDOS = 5    # cada cuántos segundos enviar lectura
TOTAL_LECTURAS     = 20   # cuántas lecturas simular (igual que antes)

# Logos para el PDF (rutas originales del proyecto)
LOGO_UNI      = r"C:\Users\Yulye\Desktop\7 semestre\Proyecto\logo_ucundinamarca.png.png"
LOGO_AGRITECH = r"C:\Users\Yulye\Desktop\7 semestre\Proyecto\AGRITECH NUEVO LOGO.jpg"

# ============================================================
# SENSORES — misma lógica que tu script original
# ============================================================

def leer_sensor_humedad():
    return round(random.uniform(40, 80), 2)

def leer_sensor_temperatura():
    return round(random.uniform(10, 25), 2)

def leer_sensor_nutrientes():
    return round(random.uniform(50, 150), 2)

# ============================================================
# API — autenticación y envío
# ============================================================

def login() -> str | None:
    """Obtiene token Sanctum. Retorna el token o None si falla."""
    try:
        res = requests.post(
            f"{API_URL}/login",
            json={"email": EMAIL, "password": PASSWORD},
            timeout=10
        )
        if res.status_code == 200:
            token = res.json().get("token")
            print(f"✅ Login exitoso. Token: {token[:20]}…")
            return token
        else:
            print(f"❌ Login fallido ({res.status_code}): {res.text}")
            return None
    except requests.exceptions.ConnectionError:
        print("❌ No se puede conectar a la API. ¿Está corriendo 'php artisan serve'?")
        return None

def enviar_lectura(token: str, sensor_id: int, value: float, unit: str) -> bool:
    """Envía una lectura al endpoint POST /api/sensors/reading"""
    try:
        res = requests.post(
            f"{API_URL}/sensors/reading",
            json={
                "sensor_id": sensor_id,
                "value":     value,
                "unit":      unit,
            },
            headers={
                "Authorization": f"Bearer {token}",
                "Accept":        "application/json",
            },
            timeout=10
        )
        return res.status_code == 201
    except requests.exceptions.RequestException as e:
        print(f"  ⚠️  Error enviando lectura: {e}")
        return False

# ============================================================
# GRÁFICA — igual que tu script original
# ============================================================

def generar_grafica(tiempos, humedades, temperaturas, nutrientes) -> str:
    plt.figure(figsize=(8, 5))
    plt.plot(tiempos, humedades,    marker="o", label="Humedad (%)",        color="#0ea5e9")
    plt.plot(tiempos, temperaturas, marker="s", label="Temperatura (°C)",   color="#f97316")
    plt.plot(tiempos, nutrientes,   marker="^", label="Nutrientes (mg/L)",  color="#22c55e")
    plt.title("Monitoreo de Sensores IoT - Cultivo de Papa\nCarmen de Carupa, Cundinamarca")
    plt.xlabel("Tiempo (s)")
    plt.ylabel("Valores")
    plt.legend()
    plt.grid(alpha=0.3)
    ruta = "grafico_sensores.png"
    plt.savefig(ruta, dpi=150, bbox_inches="tight")
    plt.close()
    print(f"📊 Gráfica guardada: {ruta}")
    return ruta

# ============================================================
# PDF — misma estructura que tu script original
# ============================================================

def encabezado(canvas, doc):
    canvas.saveState()
    try:
        if os.path.exists(LOGO_UNI):
            canvas.drawImage(LOGO_UNI,      40,  740, width=60,  height=60)
        if os.path.exists(LOGO_AGRITECH):
            canvas.drawImage(LOGO_AGRITECH, 500, 740, width=90,  height=60)
    except Exception as e:
        print("⚠️  Error cargando imágenes:", e)
    canvas.setFont("Helvetica-Bold", 10)
    canvas.drawCentredString(300, 760, "Universidad de Cundinamarca - Proyecto AGRITECH")
    canvas.restoreState()

def generar_pdf(tiempos, humedades, temperaturas, nutrientes, ruta_grafica: str,
                enviados: int, fallidos: int):
    archivo_pdf = "informe_sensores.pdf"
    doc         = SimpleDocTemplate(archivo_pdf, pagesize=letter)
    styles      = getSampleStyleSheet()
    contenido   = []

    # ── Portada ──────────────────────────────────────────────
    contenido.append(Paragraph(
        "Proyecto: AGRITECH - Tecnología con Sensores para Mejorar la Producción",
        styles['Title']
    ))
    contenido.append(Spacer(1, 20))
    contenido.append(Paragraph("Autora: Yulieth Sthephania Santana Nieto",              styles['Normal']))
    contenido.append(Paragraph("Facultad de Ingeniería - Programa de Ingeniería de Sistemas", styles['Normal']))
    contenido.append(Paragraph("Semestre: Séptimo",                                     styles['Normal']))
    contenido.append(Paragraph("Municipio: Carmen de Carupa",                           styles['Normal']))
    contenido.append(Spacer(1, 12))

    # Estado de sincronización con API
    color_sync = "#2ea043" if fallidos == 0 else "#d29922"
    contenido.append(Paragraph(
        f"<font color='{color_sync}'>● Datos enviados a API AGRITECH: {enviados}/{enviados+fallidos} exitosos</font>",
        styles['Normal']
    ))
    contenido.append(PageBreak())

    # ── Resumen ───────────────────────────────────────────────
    contenido.append(Paragraph("Informe Técnico - Monitoreo de Sensores IoT", styles['Heading1']))
    contenido.append(Spacer(1, 12))
    contenido.append(Paragraph(
        "Este informe presenta los resultados de una simulación de sensores IoT "
        "aplicados al cultivo de papa en Carmen de Carupa. Se monitorearon variables "
        "como humedad del suelo, temperatura y nutrientes en tiempo real. "
        f"Los datos fueron sincronizados con el sistema AGRITECH ({enviados} lecturas enviadas).",
        styles['Normal']
    ))
    contenido.append(Spacer(1, 12))

    # ── Tabla de datos ────────────────────────────────────────
    tabla_datos = [["Tiempo (s)", "Humedad (%)", "Temperatura (°C)", "Nutrientes (mg/L)"]]
    for i in range(len(tiempos)):
        tabla_datos.append([tiempos[i], humedades[i], temperaturas[i], nutrientes[i]])

    tabla = Table(tabla_datos, colWidths=[80, 100, 120, 120])
    tabla.setStyle(TableStyle([
        ("BACKGROUND",    (0, 0), (-1,  0), colors.HexColor("#4CAF50")),
        ("TEXTCOLOR",     (0, 0), (-1,  0), colors.white),
        ("ALIGN",         (0, 0), (-1, -1), "CENTER"),
        ("FONTNAME",      (0, 0), (-1,  0), "Helvetica-Bold"),
        ("FONTSIZE",      (0, 0), (-1, -1), 9),
        ("BOTTOMPADDING", (0, 0), (-1,  0), 8),
        ("BACKGROUND",    (0, 1), (-1, -1), colors.whitesmoke),
        ("GRID",          (0, 0), (-1, -1), 0.5, colors.black),
    ]))

    contenido.append(Paragraph("Tabla de lecturas de sensores:", styles['Heading2']))
    contenido.append(tabla)
    contenido.append(Spacer(1, 12))

    # ── Gráfica ───────────────────────────────────────────────
    contenido.append(Paragraph("Evolución de variables agroclimáticas:", styles['Heading2']))
    contenido.append(Image(ruta_grafica, width=400, height=250))
    contenido.append(Spacer(1, 12))

    # ── Análisis ──────────────────────────────────────────────
    prom_humedad    = round(statistics.mean(humedades), 2)
    max_temp        = round(max(temperaturas), 2)
    min_nutrientes  = round(min(nutrientes), 2)

    # Interpretación automática (igual que DomPDF en Laravel)
    estado_humedad = (
        "⚠️ baja, se recomienda activar riego" if prom_humedad < 45
        else "⚠️ alta, verificar drenaje"       if prom_humedad > 75
        else "✅ en rango óptimo (45–75%)"
    )
    estado_temp = (
        "🔴 baja, riesgo de helada" if max_temp < 12
        else "⚠️ alta"             if max_temp > 22
        else "✅ óptima"
    )

    analisis = (
        f"El promedio de humedad fue {prom_humedad}% — {estado_humedad}. "
        f"La temperatura máxima registrada fue {max_temp}°C — {estado_temp}. "
        f"El valor mínimo de nutrientes fue {min_nutrientes} mg/L. "
        "Estos resultados muestran la importancia del monitoreo en tiempo real "
        "para optimizar el riego y la fertilización en el cultivo de papa."
    )

    contenido.append(Paragraph("Análisis de resultados:", styles['Heading2']))
    contenido.append(Paragraph(analisis, styles['Normal']))

    # ── Build ─────────────────────────────────────────────────
    doc.build(contenido, onFirstPage=encabezado, onLaterPages=encabezado)
    print(f"✅ Informe PDF generado: {archivo_pdf}")

    try:
        os.startfile(archivo_pdf)
    except Exception:
        pass

    return archivo_pdf

# ============================================================
# MAIN
# ============================================================

def main():
    print("=" * 55)
    print("  🌿 AGRITECH — Simulador IoT + API Laravel")
    print("=" * 55)

    # 1. Login → obtener token
    token = login()
    modo_offline = token is None
    if modo_offline:
        print("⚠️  Modo OFFLINE: generando datos sin enviar a la API\n")

    tiempos      = []
    humedades    = []
    temperaturas = []
    nutrientes   = []
    enviados     = 0
    fallidos     = 0

    # 2. Bucle de simulación
    for t in range(1, TOTAL_LECTURAS + 1):
        h = leer_sensor_humedad()
        temp = leer_sensor_temperatura()
        n = leer_sensor_nutrientes()

        tiempos.append(t)
        humedades.append(h)
        temperaturas.append(temp)
        nutrientes.append(n)

        print(f"[{t:02d}/{TOTAL_LECTURAS}] H:{h}%  T:{temp}°C  N:{n}mg/L", end="  ")

        if not modo_offline:
            ok_h    = enviar_lectura(token, SENSOR_IDS["humedad"],     h,    "%")
            ok_t    = enviar_lectura(token, SENSOR_IDS["temperatura"], temp, "°C")
            ok_n    = enviar_lectura(token, SENSOR_IDS["nutrientes"],  n,    "ppm")

            if ok_h and ok_t and ok_n:
                enviados += 3
                print("→ ✅ API OK")
            else:
                fallidos += (0 if ok_h else 1) + (0 if ok_t else 1) + (0 if ok_n else 1)
                print("→ ⚠️ parcial")
        else:
            print("→ offline")

        if t < TOTAL_LECTURAS:
            time.sleep(INTERVALO_SEGUNDOS)

    # 3. Generar gráfica
    print("\n📊 Generando gráfica…")
    ruta_grafica = generar_grafica(tiempos, humedades, temperaturas, nutrientes)

    # 4. Generar PDF (igual que antes + estado API)
    print("📄 Generando PDF…")
    generar_pdf(tiempos, humedades, temperaturas, nutrientes,
                ruta_grafica, enviados, fallidos)

    print("\n" + "=" * 55)
    print(f"  Lecturas enviadas a API : {enviados}")
    print(f"  Lecturas fallidas       : {fallidos}")
    print(f"  Modo                    : {'ONLINE' if not modo_offline else 'OFFLINE'}")
    print("=" * 55)

if __name__ == "__main__":
    main()
