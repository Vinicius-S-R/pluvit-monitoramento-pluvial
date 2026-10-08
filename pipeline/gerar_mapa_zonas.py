import geopandas as gpd
from shapely.geometry import MultiPolygon, Polygon
from shapely.ops import unary_union
import math

# ============================================
# CAMINHOS
# ============================================
caminho_shp = r"C:\wamp64\www\pluvit\base de dados chuva\mapa\distrito_municipal_v2.shp"
caminho_saida = r"C:\wamp64\www\pluvit\mapa_capital_zonas.svg"

# ============================================
# LE O SHAPEFILE
# ============================================
print("Lendo shapefile...")
mapa = gpd.read_file(caminho_shp)
mapa = mapa.to_crs(epsg=4326)

# ============================================
# AGRUPA OS DISTRITOS POR REGIAO
# ============================================
print("Regiões encontradas:", mapa['nm_regiao_'].unique().tolist())

# Cria um GeoDataFrame com as 5 zonas (unindo os distritos de cada zona)
zonas_agrupadas = mapa.dissolve(by='nm_regiao_').reset_index()

print(f"\nTotal de zonas: {len(zonas_agrupadas)}")
print(zonas_agrupadas[['nm_regiao_']])

# ============================================
# CALCULA LIMITES
# ============================================
minx, miny, maxx, maxy = mapa.total_bounds
largura = maxx - minx
altura = maxy - miny

lat_media = (miny + maxy) / 2
ajuste_lat = math.cos(math.radians(lat_media))

largura_ajustada = largura
altura_ajustada = altura * ajuste_lat

viewbox_h = 1000
viewbox_w = int((largura_ajustada / altura_ajustada) * viewbox_h)

print(f"\nViewBox: {viewbox_w}x{viewbox_h}")

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

def normalizar_id(nome):
    nome = nome.lower().strip()
    trocas = {'á':'a','ã':'a','â':'a','à':'a','é':'e','ê':'e','í':'i','ó':'o','ô':'o','õ':'o','ú':'u','ç':'c',' ':'_','-':'_'}
    for k, v in trocas.items():
        nome = nome.replace(k, v)
    return nome

# ============================================
# GERA O SVG COM 5 ZONAS
# ============================================
print("\nGerando SVG com 5 zonas...")

svg = []
svg.append(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {viewbox_w} {viewbox_h}" width="100%" height="100%">')
svg.append('<style>')
svg.append('  .zona { fill: #334155; stroke: #0f172a; stroke-width: 2; cursor: pointer; transition: all 0.2s; }')
svg.append('  .zona:hover { fill: #a855f7; }')
svg.append('  .zona.active { fill: #9333ea; stroke: #c084fc; stroke-width: 2.5; }')
svg.append('</style>')

for _, zona in zonas_agrupadas.iterrows():
    nome_regiao = str(zona['nm_regiao_']).strip()
    zona_id = "zona_" + normalizar_id(nome_regiao)
    path_d = geometry_para_path(zona.geometry)
    
    svg.append(f'<path class="zona" id="{zona_id}" data-regiao="{nome_regiao}" d="{path_d}" />')

svg.append('</svg>')

with open(caminho_saida, 'w', encoding='utf-8') as f:
    f.write('\n'.join(svg))

print(f"\nSVG gerado: {caminho_saida}")
print(f"Total de zonas: {len(zonas_agrupadas)}")
print("Zonas geradas:")
for _, z in zonas_agrupadas.iterrows():
    print(f"  - {z['nm_regiao_']}")