import os
import glob
import re
import pandas as pd
from datetime import datetime

# ============================================
# CONFIGURACAO
# ============================================
pasta_raiz = r"C:\wamp64\www\pluvit\base de dados chuva\cgesp-publico\BOLETIM_PLUVIOMETRICO"
pasta_saida = r"C:\wamp64\www\pluvit\base de dados chuva\Consolidado"
os.makedirs(pasta_saida, exist_ok=True)

# ============================================
# VARRER TODOS OS ARQUIVOS XLSX
# ============================================
arquivos = glob.glob(os.path.join(pasta_raiz, "**", "*.xlsx"), recursive=True)
print(f"Pipeline CGE - Arquivos encontrados: {len(arquivos)}\n")

todos_dados = []
total_arquivos_ok = 0
total_arquivos_erro = 0

for i, arquivo in enumerate(arquivos, start=1):
    try:
        nome = os.path.basename(arquivo)
        
        # Pega o ano e mes do nome (ex: 2024-05-PLUVIOMETRIA-CGESP.xlsx)
        match = re.match(r"(\d{4})-(\d{2})", nome)
        if not match:
            print(f"[{i}] Nome fora do padrao: {nome}")
            total_arquivos_erro += 1
            continue
        
        ano = int(match.group(1))
        mes = int(match.group(2))
        
        # Le o arquivo pulando as 6 primeiras linhas (cabecalho visual)
        # A linha 6 (index 6) tem os dias, vamos usar como header
        df = pd.read_excel(arquivo, header=6)
        
        # df agora tem:
        # - Coluna 1 = nome da estacao
        # - Colunas 2-32 = dias 1-31
        # - Coluna 33 = TOTAL
        
        # Pega o nome da primeira coluna (que vai ser NaN ou "Unnamed: 0")
        coluna_estacao = df.columns[0]
        
        # Pega os dias (as colunas 1 a 31)
        colunas_dias = df.columns[1:32]  # 31 dias
        
        # Remove a ultima coluna (TOTAL) se existir
        if len(df.columns) > 32:
            df = df.drop(df.columns[32], axis=1)
        
        # ============================================
        # TRANSFORMA DE MATRIZ PARA LISTA
        # ============================================
        for _, linha in df.iterrows():
            nome_bruto = str(linha[coluna_estacao]).strip()
            
            if not nome_bruto or nome_bruto == 'nan':
                continue
            
            # Detecta se eh estacao real ou media
            eh_media = nome_bruto.upper().startswith("PRECIPITAÇÃO MÉDIA")
            
            if eh_media:
                # Eh uma media (ZL, ZC, ZO, ZS, TOTAL)
                codigo = None
                estacao = nome_bruto
                media_zona = None
                if "ZL" in nome_bruto: media_zona = "ZL"
                elif "ZC" in nome_bruto: media_zona = "ZC"
                elif "ZO" in nome_bruto: media_zona = "ZO"
                elif "ZS" in nome_bruto: media_zona = "ZS"
                elif "TOTAL" in nome_bruto.upper(): media_zona = "TOTAL"
            else:
                # Eh uma estacao real (formato "CV - Casa Verde")
                partes = nome_bruto.split(" - ", 1)
                if len(partes) == 2:
                    codigo = partes[0].strip()
                    estacao = partes[1].strip()
                else:
                    codigo = None
                    estacao = nome_bruto
                media_zona = None
            
            # Percorre cada dia
            for idx, col_dia in enumerate(colunas_dias):
                dia = idx + 1
                valor = linha[col_dia]
                
                # Pula valores NaN
                if pd.isna(valor):
                    continue
                
                # Converte para float
                try:
                    chuva = float(valor)
                except:
                    chuva = 0.0
                
                # Monta a data (valida se o dia existe naquele mes)
                try:
                    data = datetime(ano, mes, dia).strftime("%Y-%m-%d")
                except ValueError:
                    # Dia invalido (ex: 31 de fevereiro)
                    continue
                
                todos_dados.append({
                    "data_registro": data,
                    "chuva_mm": chuva,
                    "codigo": codigo,
                    "estacao": estacao,
                    "media_zona": media_zona
                })
        
        total_arquivos_ok += 1
        if i % 20 == 0:
            print(f"  [{i}/{len(arquivos)}] {nome} processado | Total acumulado: {len(todos_dados):,} registros")
    
    except Exception as e:
        total_arquivos_erro += 1
        print(f"  ERRO em {nome}: {e}")

# ============================================
# EXPORTACAO
# ============================================
if todos_dados:
    print(f"\nAgrupando {len(todos_dados):,} registros...")
    df_final = pd.DataFrame(todos_dados)
    
    # Ordena por data
    df_final = df_final.sort_values(by=["data_registro", "estacao"], ascending=[False, True])
    
    # Adiciona coluna de estado (fixo SP)
    df_final.insert(0, "estado", "SP")
    
    # Reordena colunas
    df_final = df_final[["data_registro", "chuva_mm", "codigo", "estacao", "media_zona", "estado"]]
    
    caminho_saida = os.path.join(pasta_saida, "chuvas_cge_consolidado.csv")
    df_final.to_csv(caminho_saida, index=False, sep=";", encoding="utf-8")
    
    print(f"\n============================================")
    print(f"PIPELINE CGE CONCLUIDA!")
    print(f"============================================")
    print(f"Arquivos processados : {total_arquivos_ok}")
    print(f"Arquivos com erro    : {total_arquivos_erro}")
    print(f"Total de linhas      : {len(df_final):,}")
    print(f"Arquivo final        : {caminho_saida}")
    print(f"============================================")
else:
    print("\nNenhum dado foi extraido.")