import geopandas as gpd
from shapely.geometry import MultiPolygon, Polygon

# ============================================
# CAMINHOS
# ============================================
caminho_shp = r"C:\wamp64\www\pluvit\base de dados chuva\mapa\distrito_municipal_v2.shp"
caminho_saida = r"C:\wamp64\www\pluvit\mapa_capital.svg"

# ============================================
# LE O SHAPEFILE
# ============================================
print("Lendo shapefile...")
mapa = gpd.read_file(caminho_shp)
mapa = mapa.to_crs(epsg=4326)

minx, miny, maxx, maxy = mapa.total_bounds
largura = maxx - minx
altura = maxy - miny

# Corrige a proporcao (latitude tem escala diferente em SP)
import math
lat_media = (miny + maxy) / 2
ajuste_lat = math.cos(math.radians(lat_media))

# Aplica ajuste
largura_ajustada = largura
altura_ajustada = altura * ajuste_lat

# Calcula viewBox mantendo proporcao
viewbox_h = 1000
viewbox_w = int((largura_ajustada / altura_ajustada) * viewbox_h)

print(f"Total de distritos: {len(mapa)}")
print(f"ViewBox: {viewbox_w}x{viewbox_h}")

# ============================================
# FUNCOES
# ============================================
def coord_para_svg(lon, lat):
    x = ((lon - minx) / largura_ajustada) * viewbox_w
    y = viewbox_h - (((lat - miny) * ajuste_lat) / altura_ajustada) * viewbox_h
    return f"{x:.2f},{y:.2f}"

def geometry_para_path(geom):
    paths = []
    if isinstance(geom, Polygon):
        aneis = [geom.exterior] + list(geom.interiors)
        for anel in aneis:
            coords = [coord_para_svg(x, y) for x, y in anel.coords]
            if coords:
                paths.append("M " + " L ".join(coords) + " Z")
    elif isinstance(geom, MultiPolygon):
        for poly in geom.geoms:
            aneis = [poly.exterior] + list(poly.interiors)
            for anel in aneis:
                coords = [coord_para_svg(x, y) for x, y in anel.coords]
                if coords:
                    paths.append("M " + " L ".join(coords) + " Z")
    return " ".join(paths)

def limpar_nome(nome):
    nome = nome.lower().strip()
    trocas = {'á':'a','ã':'a','â':'a','à':'a','é':'e','ê':'e','í':'i','ó':'o','ô':'o','õ':'o','ú':'u','ç':'c','/':'_',' ':'_','\'':'','-':'_'}
    for k, v in trocas.items():
        nome = nome.replace(k, v)
    return nome

# ============================================
# GERA O SVG
# ============================================
print("Gerando SVG com distritos individuais...")

svg = []
svg.append(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {viewbox_w} {viewbox_h}" width="100%" height="100%">')
svg.append('<style>')
svg.append('  .distrito { fill: #334155; stroke: #0f172a; stroke-width: 1.5; cursor: pointer; transition: all 0.2s; }')
svg.append('  .distrito:hover { fill: #3b82f6; }')
svg.append('  .distrito.active { fill: #2563eb; stroke: #60a5fa; stroke-width: 2; }')
svg.append('</style>')

for _, linha in mapa.iterrows():
    nome = str(linha['nm_distrit']).strip()
    codigo = str(linha['cd_distrit']).strip()
    regiao = str(linha['nm_regiao_']).strip() if 'nm_regiao_' in linha else ''
    
    id_seguro = limpar_nome(nome)
    path_d = geometry_para_path(linha.geometry)
    
    svg.append(f'<path class="distrito" id="{id_seguro}" data-nome="{nome}" data-codigo="{codigo}" data-regiao="{regiao}" d="{path_d}" />')

svg.append('</svg>')

with open(caminho_saida, 'w', encoding='utf-8') as f:
    f.write('\n'.join(svg))

print(f"\nSVG gerado: {caminho_saida}")
print(f"Total de distritos: {len(mapa)}")
print(f"ViewBox: {viewbox_w}x{viewbox_h}")