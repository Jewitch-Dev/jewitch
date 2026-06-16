Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

siteDir = fso.GetParentFolderName(WScript.ScriptFullName)
scriptPath = siteDir & "\scripts\publish-site.ps1"

message = InputBox("Commit message:", "Publish Site", "Update site")

If Trim(message) = "" Then
    message = "Update site"
End If

Set env = shell.Environment("Process")
env("JEWITCH_COMMIT_MESSAGE") = message

command = "powershell.exe -NoProfile -ExecutionPolicy Bypass -Command " & _
    Chr(34) & "& '" & scriptPath & "' -CommitMessage $env:JEWITCH_COMMIT_MESSAGE" & Chr(34)

exitCode = shell.Run(command, 1, True)

If exitCode = 0 Then
    MsgBox "Publish flow finished.", vbInformation, "Publish Site"
Else
    MsgBox "Publish flow failed. The PowerShell window should show the error.", vbExclamation, "Publish Site"
End If
