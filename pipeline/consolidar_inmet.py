import os
import glob
import pandas as pd

# ============================================
# CONFIGURACAO DOS CAMINHOS
# ============================================
pasta_bruta   = r"C:\wamp64\www\pluvit\base de dados chuva\todos_arquivos"
pasta_destino = r"C:\wamp64\www\pluvit\base de dados chuva\Consolidado"

os.makedirs(pasta_destino, exist_ok=True)

# ============================================
# VARRER TODOS OS CSVs DA PASTA UNICA
# ============================================
arquivos_csv = glob.glob(os.path.join(pasta_bruta, "*.csv"))
total_arquivos = len(arquivos_csv)

print(f"Pipeline Completa - 2020 a 2026")
print(f"Pasta: {pasta_bruta}")
print(f"Arquivos encontrados: {total_arquivos}\n")

dados_sao_paulo = []
total_arquivos_sp = 0
arquivos_com_erro = 0

for i, arquivo in enumerate(arquivos_csv, start=1):
    try:
        # Mostra progresso a cada 100 arquivos
        if i % 100 == 0:
            print(f"  Processados: {i}/{total_arquivos} | SP encontrados: {total_arquivos_sp}")

        # Le as primeiras linhas do cabecalho (metadados do INMET)
        with open(arquivo, "r", encoding="ISO-8859-1") as f:
            linhas_cabecalho = [f.readline() for _ in range(9)]

        # ============================================
        # PROCURA A UF E O NOME DA ESTACAO NO CABECALHO
        # ============================================
        uf_encontrada = ""
        nome_estacao = "Estacao Desconhecida"

        for linha in linhas_cabecalho:
            partes = [p.strip().upper() for p in linha.split(";")]
            if "SP" in partes:
                uf_encontrada = "SP"

            if any("ESTACAO:" in p for p in partes):
                partes_original = [p.strip() for p in linha.split(";")]
                if len(partes_original) > 1:
                    nome_estacao = partes_original[1]

        # Se nao for de SP, pula o arquivo
        if uf_encontrada != "SP":
            continue

        # ============================================
        # LE A TABELA DE DADOS A PARTIR DA LINHA 9
        # ============================================
        df_chuva = pd.read_csv(arquivo, sep=";", encoding="ISO-8859-1", skiprows=8)
        df_chuva = df_chuva.loc[:, ~df_chuva.columns.str.contains('^Unnamed')]

        df_chuva["Estado"]  = "SP"
        df_chuva["Estacao"] = nome_estacao

        dados_sao_paulo.append(df_chuva)
        total_arquivos_sp += 1

    except Exception as e:
        arquivos_com_erro += 1
        # So mostra erro nos primeiros para nao poluir
        if arquivos_com_erro <= 10:
            print(f"  Erro em {os.path.basename(arquivo)}: {e}")

# ============================================
# EMPACOTAMENTO E EXPORTACAO
# ============================================
if dados_sao_paulo:
    print(f"\nAgrupando {total_arquivos_sp} arquivos de SP...")
    df_consolidado = pd.concat(dados_sao_paulo, ignore_index=True)

    # Renomeia colunas do INMET
    df_consolidado = df_consolidado.rename(columns={
        'PRECIPITAÇÃO TOTAL, HORÁRIO (mm)': 'Chuva_mm',
        'Hora UTC': 'Hora'
    })

    # Mantem apenas colunas essenciais
    colunas_finais = ['Data', 'Hora', 'Chuva_mm', 'Estacao', 'Estado']
    df_consolidado = df_consolidado[colunas_finais]

    # Limpa o campo Hora (remove " UTC")
    df_consolidado['Hora'] = df_consolidado['Hora'].astype(str).str.replace(' UTC', '', regex=False).str.strip()

    # Converte virgula decimal para ponto
    df_consolidado['Chuva_mm'] = df_consolidado['Chuva_mm'].astype(str).str.replace(',', '.', regex=False)
    df_consolidado['Chuva_mm'] = pd.to_numeric(df_consolidado['Chuva_mm'], errors='coerce').fillna(0.0)

    # Converte data para formato MySQL
    df_consolidado['Data'] = pd.to_datetime(df_consolidado['Data'], format='%Y/%m/%d', errors='coerce')
    df_consolidado = df_consolidado.dropna(subset=['Data'])
    df_consolidado['Data'] = df_consolidado['Data'].dt.strftime('%Y-%m-%d')

    # Ordena por data decrescente
    df_consolidado = df_consolidado.sort_values(by=['Data', 'Hora'], ascending=False)

    # Salva
    caminho_saida = os.path.join(pasta_destino, "chuvas_sp_consolidado.csv")
    df_consolidado.to_csv(caminho_saida, index=False, sep=";", encoding="utf-8")

    # ============================================
    # RELATORIO FINAL
    # ============================================
    print(f"\n============================================")
    print(f"PIPELINE CONCLUIDA COM SUCESSO!")
    print(f"============================================")
    print(f"Arquivos totais varridos : {total_arquivos}")
    print(f"Arquivos de SP unificados: {total_arquivos_sp}")
    print(f"Arquivos com erro        : {arquivos_com_erro}")
    print(f"Total de linhas geradas  : {len(df_consolidado):,}")
    print(f"Arquivo final            : {caminho_saida}")
    print(f"============================================")
else:
    print("\nNenhum dado de SP encontrado.")