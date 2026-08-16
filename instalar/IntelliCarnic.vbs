' ==================================================
' IntelliCarnic - Lanzador Invisible (Sin Ventana Negra)
' Este archivo ejecuta abrir_sistema.bat de forma
' completamente silenciosa, sin mostrar ninguna
' ventana de consola al usuario.
' ==================================================

Dim scriptDir
scriptDir = CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptFullName)

Dim batPath
batPath = scriptDir & "\abrir_sistema.bat"

Dim WshShell
Set WshShell = CreateObject("WScript.Shell")

' Ejecutar el bat completamente invisible (0 = oculto, False = no esperar)
WshShell.Run Chr(34) & batPath & Chr(34), 0, False

Set WshShell = Nothing
