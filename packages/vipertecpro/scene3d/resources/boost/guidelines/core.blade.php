## vipertecpro/scene3d

A NativePHP Mobile plugin

### Installation

```bash
composer require vipertecpro/scene3d
```

### PHP Usage (Livewire/Blade)

Use the `Scene3d` facade:

@verbatim
<code-snippet name="Using Scene3d Facade" lang="php">
use Vipertecpro\Scene3d\Facades\Scene3d;

// Execute the plugin functionality
$result = Scene3d::execute(['option1' => 'value']);

// Get the current status
$status = Scene3d::getStatus();
</code-snippet>
@endverbatim

### Available Methods

- `Scene3d::execute()`: Execute the plugin functionality
- `Scene3d::getStatus()`: Get the current status

### Events

- `Scene3dCompleted`: Listen with `#[OnNative(Scene3dCompleted::class)]`

@verbatim
<code-snippet name="Listening for Scene3d Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Vipertecpro\Scene3d\Events\Scene3dCompleted;

#[OnNative(Scene3dCompleted::class)]
public function handleScene3dCompleted($result, $id = null)
{
    // Handle the event
}
</code-snippet>
@endverbatim

### JavaScript Usage (Vue/React/Inertia)

@verbatim
<code-snippet name="Using Scene3d in JavaScript" lang="javascript">
import { scene3d } from '@vipertecpro/scene3d';

// Execute the plugin functionality
const result = await scene3d.execute({ option1: 'value' });

// Get the current status
const status = await scene3d.getStatus();
</code-snippet>
@endverbatim