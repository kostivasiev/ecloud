<?php

namespace Database\Seeders\Software;

use App\Models\V2\Script;
use App\Models\V2\Software;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use function app;

class McafeeWindowsSoftwareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'name' => 'McAfee Antivirus',
            'visibility' => Software::VISIBILITY_PUBLIC,
            'platform' => Software::PLATFORM_WINDOWS
        ];

        if (app()->environment() != 'production') {
            $data['id'] = 'soft-mcafee-' . strtolower(Software::PLATFORM_WINDOWS);
        }

        $software = Software::factory()->create($data);

        Script::factory()
            ->count(1)
            ->state(new Sequence(
                [
                    'name' => 'Install',
                    'sequence' => 1,
                    'script' => <<<'EOM'
$sourcePath = "http://80.244.178.135/Windows/McAfee/FramePkg574.exe"
$AgentHash = "ECE2A8E618E632C377D242806A2586F75DB6F9E634B133690CA78B170C0C9559"
$AgentVersion = "5.07.4001"
$TempPath = $env:TEMP+"\"+[System.Guid]::NewGuid().ToString()
$TempBinPath = $TempPath+"\FramePkg574.exe"

function DownloadFile($Source, $Destination, $Username, $Password)
{
    try
    {
        $Web = New-Object System.Net.WebClient
        if (![string]::IsNullOrEmpty($Username))
        {
            $Web.Credentials = New-Object System.Net.Networkcredential($Username, $Password)
        }

        $Web.DownloadFile($Source, $Destination)
    }
    catch
    {
        throw "Error downloading file from [$Source] to [$Destination]: $($_.Exception.Message)"
    }
}

function Invoke-Process
{    
    [CmdletBinding()]
    Param
    (
        [Parameter(Mandatory=$true, Position=0)][string]$Path,
        [Parameter(Mandatory=$false, Position=1)][string[]]$Arguments,
        [Parameter(Mandatory=$false)][switch]$Async,
        [Parameter(Mandatory=$false)][int]$Timeout=0,
        [Parameter(Mandatory=$false)][string]$Username=$null,
        [Parameter(Mandatory=$false)][string]$Password=$null,
        [Parameter(Mandatory=$false)][string]$WorkingDirectory=$null,
        [Parameter(Mandatory=$false, ParameterSetName="ValidateExitCode")][switch]$ValidateExitCode,
        [Parameter(Mandatory=$false, ParameterSetName="ValidateExitCode")][array]$ExitCodes=0
    )

    try
    {
        $Process = New-Object System.Diagnostics.Process
        $Process.StartInfo.Filename = $Path
        $Process.StartInfo.Arguments = ($Arguments -join " ")
        $Process.StartInfo.RedirectStandardOutput = $True
        $Process.StartInfo.RedirectStandardError = $True
        $Process.StartInfo.UseShellExecute = $false
        if ([string]::IsNullOrEmpty($WorkingDirectory) -eq $false)
        {
            $Process.StartInfo.WorkingDirectory = $WorkingDirectory
        }
        else
        {
            $Process.StartInfo.WorkingDirectory = (Get-Location).Path
        }
        if (([string]::IsNullOrEmpty($Username) -eq $false) -and ([string]::IsNullOrEmpty($Password) -eq $false))
        {
            $Process.StartInfo.UserName = $Username
            $Process.StartInfo.Password = (ConvertTo-SecureString -String $Password -AsPlainText -Force)
        }
                
        $StdOutBuilder = New-Object -TypeName System.Text.StringBuilder
        $StdErrBuilder = New-Object -TypeName System.Text.StringBuilder

        $OutputEventScriptBlock = {
            if (![String]::IsNullOrEmpty($EventArgs.Data))
            {
                $Event.MessageData.AppendLine($EventArgs.Data)
            }
        }
        $StdOutEvent = Register-ObjectEvent -InputObject $Process -Action $OutputEventScriptBlock -EventName 'OutputDataReceived' -MessageData $StdOutBuilder
        $StdErrEvent = Register-ObjectEvent -InputObject $Process -Action $OutputEventScriptBlock -EventName 'ErrorDataReceived' -MessageData $StdErrBuilder

        $Process.Start()

        if ($Async -eq $true)
        {
            return
        }

        $Process.BeginOutputReadLine()
        $Process.BeginErrorReadLine()

        if ($Timeout -gt 0)
        {
            if (!$Process.WaitForExit($Timeout * 1000))
            {
                $Process.Kill()
                throw ("Timed out after [{0}] seconds" -f $Timeout)
            }
        }
        else
        {
            $Process.WaitForExit()
        }

        Unregister-Event -SourceIdentifier $StdOutEvent.Name
        Unregister-Event -SourceIdentifier $StdErrEvent.Name

        $_this = New-Object PSObject -Property `
        @{
            stdOut = $StdOutBuilder.ToString()
            stdErr = $StdErrBuilder.ToString()
            exitCode = $Process.ExitCode
        }

        if ($ValidateExitCode)
        {
            if (!($ExitCodes -contains $_this.exitCode))
            {
                throw ("{0} exited with unexpected exitcode: {1}" -f $Path, (Format-ProcessOutput -Output $_this))
            }
        }

        return $_this
    }
    catch
    {
        throw ("Exception thrown executing process; Path=[{0}] Arguments=[{1}] Exception=[{2}]" -f $Path, ($Arguments -join " "), $_.Exception)
    }
}

function Format-ProcessOutput($Output)
{
    return "ExitCode=[$($Output.exitCode)] StdOut=[$($Output.stdOut)] StdErr=[$($Output.stdErr)]"
}

function Get-AgentSoftwareProduct() 
{
    return (Get-CimInstance -Class Win32_Product -Filter "Name='Mcafee Agent'")
}

try {
    # Check for existing agent
    $agent = Get-AgentSoftwareProduct
    if ($null -ne $agent -and $agent.Version -eq $AgentVersion) {
        Write-Output "Agent Version [$($agent.Version)] already installed."
        exit 0
    } 

    New-Item -Path $TempPath -ItemType Directory -Force | Out-Null

    # Download agent
    try {
        DownloadFile -Source $sourcePath -Destination $TempBinPath -Username "mcafee.installer" -Password "K-bWPIXuMF0p"
    } 
    catch {
        Write-Output $_.Exception.Message
        exit 3
    }
    
    if ((Get-FileHash -Path $TempBinPath -Algorithm SHA256).hash -ne $AgentHash) {
        Write-Output "File hash does not match"
        exit 3
    }

    # Install agent
    Invoke-Process -Path $TempBinPath -Arguments "/install=agent /Silent" -ValidateExitCode -ExitCodes 0 | Out-Null
}
catch {
    Write-Output "Failed to install McAfee Agent: $($_.Exception.Message)"
    exit 1
}
finally {
    if (Test-Path -Path $TempPath)
    {
        Remove-Item -Path $TempPath -Recurse -Force
    }
}
EOM
                ],
            ))
            ->create([
                'software_id' => $software->id,
            ]);
    }
}
