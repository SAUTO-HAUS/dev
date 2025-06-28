# PowerShell script to create sample SVG flag files
$flagsDir = "c:\xampp\htdocs\public_html\media\images\flags"

# Create flags directory if it doesn't exist
if (!(Test-Path -Path $flagsDir)) {
    New-Item -ItemType Directory -Path $flagsDir -Force
}

# List of country codes from SQL file
$countryCodes = @(
    # European countries
    "at", "be", "bg", "hr", "cy", "cz", "dk", "ee", "fi", "fr", 
    "de", "gr", "hu", "ie", "it", "lv", "lt", "lu", "mt", "nl", 
    "pl", "pt", "ro", "sk", "si", "es", "se", "gb", "us",
    # Non-European countries
    "ca", "jp", "cn", "au", "br", "in", "za"
)

# Basic SVG template for a flag
$svgTemplate = @"
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480">
  <title>{0} Flag</title>
  <rect width="640" height="480" fill="#f0f0f0"/>
  <text x="50%" y="50%" font-family="Arial" font-size="60" text-anchor="middle" dominant-baseline="middle" fill="#555">{1}</text>
</svg>
"@

# Create an SVG file for each country code
foreach ($code in $countryCodes) {
    $countryName = $code.ToUpper()
    $svgContent = $svgTemplate -f $countryName, $countryName
    $filePath = Join-Path -Path $flagsDir -ChildPath "$code.svg"
    
    # Write SVG content to file
    Set-Content -Path $filePath -Value $svgContent -Force
    
    Write-Host "Created flag for $countryName at $filePath"
}

Write-Host "All flag files have been created."
