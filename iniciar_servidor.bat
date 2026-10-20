@echo off
title Amora Artesanal - Servidor Local
echo ===================================================
echo     Iniciando Servidor Web - Amora Artesanal
echo ===================================================
echo.
echo O site ficara ativo enquanto esta janela estiver aberta.
echo Pressione Ctrl+C para encerrar quando terminar.
echo.

:: Abre o navegador automaticamente apos 2 segundos
start "" http://localhost:8000

:: Inicia o servidor PHP usando o PHP do XAMPP ou o padrao do sistema
if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S localhost:8000
) else (
    php -S localhost:8000
)

pause
