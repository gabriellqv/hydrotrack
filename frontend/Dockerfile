# ============================================================
# Dockerfile do Frontend (Vue.js 3 / Vite)
#
# Utiliza Node.js 22 Alpine para instalar dependências e
# iniciar o servidor de desenvolvimento do Vite com hot reload.
# Em produção, pode ser adaptado para multi-stage com Nginx.
# ============================================================

FROM node:22-alpine

WORKDIR /app

# Copiar manifestos de dependências primeiro (cache de layer)
COPY package.json package-lock.json ./

# Instalar dependências
RUN npm ci

# Copiar código-fonte
COPY . .

# Expor a porta do Vite dev server
EXPOSE 5173

# Iniciar o servidor de desenvolvimento acessível externamente
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
