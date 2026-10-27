from pathlib import Path
from reportlab.platypus import SimpleDocTemplate,Paragraph,Spacer,Table,TableStyle,PageBreak,Preformatted,KeepTogether,Image
from reportlab.lib.styles import getSampleStyleSheet,ParagraphStyle
from reportlab.lib import colors
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.enums import TA_LEFT
from xml.sax.saxutils import escape
import re
root=Path(__file__).resolve().parents[1]
for name,file in [('Arial','arial.ttf'),('ArialBold','arialbd.ttf'),('ArialItalic','ariali.ttf')]:pdfmetrics.registerFont(TTFont(name,str(Path('C:/Windows/Fonts')/file)))
pdfmetrics.registerFontFamily('Arial',normal='Arial',bold='ArialBold',italic='ArialItalic',boldItalic='ArialBold')
styles=getSampleStyleSheet()
styles.add(ParagraphStyle(name='IOBody',fontName='Arial',fontSize=10,leading=15,spaceAfter=8,textColor=colors.HexColor('#344054')))
styles.add(ParagraphStyle(name='IOTitle',fontName='ArialBold',fontSize=22,leading=28,spaceAfter=20,textColor=colors.HexColor('#18295b')))
styles.add(ParagraphStyle(name='IOH2',fontName='ArialBold',fontSize=14,leading=19,spaceBefore=14,spaceAfter=9,textColor=colors.HexColor('#18295b'),keepWithNext=True))
styles.add(ParagraphStyle(name='IOCell',fontName='Arial',fontSize=8,leading=11,spaceAfter=0))
styles.add(ParagraphStyle(name='IOCode',fontName='Courier',fontSize=8,leading=11,spaceAfter=9))
def fmt(s):
 s=escape(s);s=re.sub(r'\*\*(.+?)\*\*',r'<b>\1</b>',s);s=re.sub(r'`([^`]+)`',r'\1',s);return s
story=[Image(str(root/'public/images/umg/escudo-umg.png'),width=82,height=82,hAlign='LEFT'),Spacer(1,16)]
for index,file in enumerate(['INFORME_TECNICO.md','MODELO.md','MANUAL_USUARIO.md','BITACORA_IA.md']):
 if index:story.append(PageBreak())
 lines=(root/'docs'/file).read_text(encoding='utf-8').splitlines();i=0
 while i<len(lines):
  line=lines[i].strip()
  if not line:i+=1;continue
  if line.startswith('```'):
   code=[];i+=1
   while i<len(lines) and not lines[i].startswith('```'):code.append(lines[i]);i+=1
   story.append(Preformatted('\n'.join(code),styles['IOCode']));i+=1;continue
  if line.startswith('|'):
   rows=[]
   while i<len(lines) and lines[i].strip().startswith('|'):
    cells=[c.strip() for c in lines[i].strip().strip('|').split('|')]
    if not all(re.fullmatch(r'[-: ]+',c) for c in cells):rows.append([Paragraph(fmt(c),styles['IOCell']) for c in cells])
    i+=1
   if rows:
    n=len(rows[0]);width=475
    widths=[width/n]*n
    if n==4:widths=[52,115,115,193]
    t=Table(rows,colWidths=widths,repeatRows=1,hAlign='LEFT');t.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),colors.HexColor('#ecf3ff')),('VALIGN',(0,0),(-1,-1),'TOP'),('LEFTPADDING',(0,0),(-1,-1),7),('RIGHTPADDING',(0,0),(-1,-1),7),('TOPPADDING',(0,0),(-1,-1),7),('BOTTOMPADDING',(0,0),(-1,-1),7),('LINEBELOW',(0,0),(-1,-1),.3,colors.HexColor('#e4e7ec'))]));story.extend([t,Spacer(1,10)])
   continue
  if line.startswith('# '):story.append(Paragraph(fmt(line[2:]),styles['IOTitle']))
  elif line.startswith('## '):story.append(Paragraph(fmt(line[3:]),styles['IOH2']))
  else:story.append(Paragraph(fmt(line),styles['IOBody']))
  i+=1
out=root/'docs'/'Informe_Tecnico_IO.pdf'
def footer(canvas,doc):
 canvas.setFont('Arial',8);canvas.setFillColor(colors.HexColor('#667085'));canvas.drawString(60,34,'Carlos Augusto Lopez Urizar | 6.o semestre | UMG 2026');canvas.drawRightString(535,34,str(doc.page))
SimpleDocTemplate(str(out),pagesize=(595.28,841.89),rightMargin=60,leftMargin=60,topMargin=55,bottomMargin=55,title='Docencia - Informe tecnico y auditoria de IA',author='Carlos Augusto Lopez Urizar').build(story,onFirstPage=footer,onLaterPages=footer)
print(out)
