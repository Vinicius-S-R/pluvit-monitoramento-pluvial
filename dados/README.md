# Dados do Projeto

Esta pasta **não contém os dados brutos** (muito grandes para o GitHub).

## Fontes de dados

### 🛰️ INMET (Instituto Nacional de Meteorologia)
- **Formato original:** CSV
- **Granularidade:** por hora
- **Cobertura:** estado de São Paulo
- **Período utilizado:** 2020 a 2026
- **Total de arquivos processados:** 3.515
- **Total de linhas após consolidação:** 2.004.768
- **Link oficial:** https://portal.inmet.gov.br/dadoshistoricos

### 🏙️ CGE-SP (Centro de Gerenciamento de Emergências)
- **Formato original:** XLSX (Excel)
- **Granularidade:** por dia
- **Cobertura:** capital de São Paulo
- **Período utilizado:** 2010 a 2026
- **Total de arquivos processados:** 201
- **Total de linhas após consolidação:** 236.800
- **Link oficial:** https://www.prefeitura.sp.gov.br/cidade/secretarias/obras/cge/

## Como reproduzir os dados

1. Baixe os dados brutos nos links oficiais acima
2. Coloque na pasta `pipeline/entrada/`
3. Execute os scripts `consolidar_inmet.py` e `consolidar_cge.py`
4. Os arquivos consolidados serão gerados na pasta `Consolidado/`