Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

siteDir = fso.GetParentFolderName(WScript.ScriptFullName)
scriptPath = siteDir & "\scripts\new-post.ps1"

title = InputBox("Post title:", "New Blog Post")

If Trim(title) = "" Then
    WScript.Quit 0
End If

description = InputBox("Post description:", "New Blog Post")

Set env = shell.Environment("Process")
env("JEWITCH_POST_TITLE") = title
env("JEWITCH_POST_DESCRIPTION") = description

command = "powershell.exe -NoProfile -ExecutionPolicy Bypass -Command " & _
    Chr(34) & "& '" & scriptPath & "' -Title $env:JEWITCH_POST_TITLE -Description $env:JEWITCH_POST_DESCRIPTION" & Chr(34)

exitCode = shell.Run(command, 1, True)

If exitCode = 0 Then
    MsgBox "Blog post created in content\posts.", vbInformation, "New Blog Post"
Else
    MsgBox "The blog post was not created. Try running scripts\new-post.ps1 from PowerShell to see the error.", vbExclamation, "New Blog Post"
End If
