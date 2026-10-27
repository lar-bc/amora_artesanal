@echo off
title Enviar alteracoes para o GitHub - Amora Artesanal
echo ===================================================
echo     Enviando alteracoes para o seu GitHub
echo ===================================================
echo.
git add .
git commit -m "update: atualizacoes do projeto Amora Artesanal"
git push origin main
echo.
echo ===================================================
echo Processo concluido com sucesso!
echo ===================================================
pause
