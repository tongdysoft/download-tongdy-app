setlocal enabledelayedexpansion
mkdir png8
for %%x in (*.png) do (
    magick.exe %%x -resize 200x200 PNG8:png8\%%x
)
