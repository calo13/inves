"""Genera el manual imprimible a partir de los documentos Markdown del proyecto."""
from pathlib import Path
import re
from xml.sax.saxutils import escape

from reportlab.lib import colors
from reportlab.lib.styles import ParagraphStyle
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, Image,
)

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'output/pdf/Manual_Usuario_IO.pdf'
OUT.parent.mkdir(parents=True, exist_ok=True)
NAVY = colors.HexColor('#18295b')
RED = colors.HexColor('#af263b')
GRAY = colors.HexColor('#475467')
for name, filename in [('Arial', 'arial.ttf'), ('ArialBold', 'arialbd.ttf'), ('ArialItalic', 'ariali.ttf')]:
    pdfmetrics.registerFont(TTFont(name, str(Path('C:/Windows/Fonts') / filename)))
pdfmetrics.registerFontFamily('Arial', normal='Arial', bold='ArialBold', italic='ArialItalic', boldItalic='ArialBold')

BODY = ParagraphStyle('Body', fontName='Arial', fontSize=10.4, leading=15.3, spaceAfter=9, textColor=GRAY)
TITLE = ParagraphStyle('Title', fontName='ArialBold', fontSize=23, leading=28, textColor=NAVY, spaceAfter=18)
SUB = ParagraphStyle('Sub', fontName='ArialBold', fontSize=12.2, leading=16, textColor=NAVY, spaceBefore=10, spaceAfter=7, keepWithNext=True)
SMALL = ParagraphStyle('Small', parent=BODY, fontSize=9, leading=13)
CELL = ParagraphStyle('Cell', parent=BODY, fontSize=9.2, leading=13, spaceAfter=0)
STEP = ParagraphStyle('Step', parent=BODY, leftIndent=15, firstLineIndent=-15, spaceAfter=7)
KICKER = ParagraphStyle('Kicker', fontName='ArialBold', fontSize=9, leading=12, textColor=RED, spaceAfter=10)


def fmt(text):
    text = escape(text.replace('—', '-').replace('–', '-'))
    text = re.sub(r'\*\*(.+?)\*\*', r'<b>\1</b>', text)
    return re.sub(r'`([^`]+)`', r'\1', text)


def para(text, style=BODY):
    return Paragraph(fmt(text), style)


def markdown(text, summary=False):
    result = []
    lines = text.splitlines()
    i = 0
    while i < len(lines):
        line = lines[i].strip()
        i += 1
        if not line:
            continue
        if line.startswith('|'):
            rows = []
            while True:
                cells = [c.strip() for c in line.strip('|').split('|')]
                if not all(re.fullmatch(r'[-: ]+', c) for c in cells):
                    rows.append([para(c, CELL) for c in cells])
                if i >= len(lines) or not lines[i].strip().startswith('|'):
                    break
                line = lines[i].strip()
                i += 1
            table = Table(rows, colWidths=[154, 329.28], repeatRows=1, hAlign='LEFT')
            table.setStyle(TableStyle([
                ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#eaf0fa')),
                ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, colors.HexColor('#f8fafc')]),
                ('VALIGN', (0, 0), (-1, -1), 'TOP'),
                ('LEFTPADDING', (0, 0), (-1, -1), 9), ('RIGHTPADDING', (0, 0), (-1, -1), 9),
                ('TOPPADDING', (0, 0), (-1, -1), 7), ('BOTTOMPADDING', (0, 0), (-1, -1), 7),
                ('LINEBELOW', (0, 0), (-1, -1), .3, colors.HexColor('#dce3ee')),
            ]))
            result.extend([table, Spacer(1, 9)])
        elif line.startswith('# '):
            result.append(para(line[2:], TITLE))
        elif line.startswith('## '):
            result.append(para(line[3:], SUB if summary else TITLE))
        elif line.startswith('### '):
            result.append(para(line[4:], SUB))
        elif re.match(r'^\d+\. ', line):
            result.append(para(line, STEP))
        elif line.startswith('- '):
            result.append(para('• ' + line[2:], STEP))
        else:
            result.append(para(line))
    return result


chapters = re.split(r'(?m)^## ', (ROOT / 'docs/MANUAL_USUARIO.md').read_text(encoding='utf-8'))[1:]
story = [
    Image(str(ROOT / 'public/images/umg/escudo-umg.png'), width=90, height=90, hAlign='LEFT'),
    Spacer(1, 22), para('UNIVERSIDAD MARIANO GÁLVEZ DE GUATEMALA', KICKER),
    para('Proyecto IO UMG', TITLE),
    Paragraph('Resumen de funcionamiento<br/>y manual de usuario', ParagraphStyle('CoverTitle', parent=TITLE, fontSize=27, leading=33)),
    para('Optimización de tickets y acompañamiento docente', BODY),
    Spacer(1, 16),
    para('**Curso:** Investigación de Operaciones'),
    para('**Estudiante:** Carlos Augusto Lopez Urizar'),
    para('**Semestre:** 6.º'),
    para('**Versión:** 28/10/2026  ·  **Entrega:** 30/10/2026'),
    Spacer(1, 14), para('CONTENIDO', KICKER),
    para('Resumen de funcionamiento .................................................. 2', SMALL),
]
for n, chapter in enumerate(chapters, start=3):
    heading = chapter.splitlines()[0]
    story.append(para(f'{heading}  ·  p. {n}', SMALL))
story.append(PageBreak())
summary = (ROOT / 'docs/RESUMEN_FUNCIONAMIENTO.md').read_text(encoding='utf-8')
# La identificación académica ya aparece en portada.
summary = re.sub(r'(?m)^(Universidad Mariano.*|\*\*Estudiante:.*|\*\*Documento:.*)\n', '', summary)
story.extend(markdown(summary, summary=True))
for index, chapter in enumerate(chapters, start=1):
    story.extend([PageBreak(), para(f'MANUAL DE USUARIO  /  {index:02}', KICKER)])
    story.extend(markdown('## ' + chapter))


def footer(canvas, doc):
    canvas.saveState()
    canvas.setStrokeColor(colors.HexColor('#dce3ee'))
    canvas.line(56, 49, 539.28, 49)
    canvas.setFont('Arial', 8)
    canvas.setFillColor(GRAY)
    canvas.drawString(56, 34, 'Carlos Augusto Lopez Urizar  |  6.º semestre  |  UMG 2026')
    canvas.drawRightString(539.28, 34, str(doc.page))
    if doc.page > 1:
        canvas.setFont('Arial', 8)
        canvas.drawString(56, 811, 'PROYECTO IO UMG  /  GUÍA DE USO')
        canvas.setStrokeColor(RED)
        canvas.setLineWidth(2)
        canvas.line(56, 800, 91, 800)
    canvas.restoreState()


SimpleDocTemplate(
    str(OUT), pagesize=(595.28, 841.89), leftMargin=56, rightMargin=56,
    topMargin=61, bottomMargin=65,
    title='Proyecto IO UMG - Resumen y manual de usuario',
    author='Carlos Augusto Lopez Urizar', subject='Investigación de Operaciones - 6.º semestre',
).build(story, onFirstPage=footer, onLaterPages=footer)
print(OUT)
