<?php

namespace App\Http\Controllers;

use App\Models\ApplicationEvent;
use App\Models\ApplicationEventEmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ApplicationEventController extends Controller
{
    //
    public function index()
    {
        $events = ApplicationEvent::with('emailTemplate')->get();
        $templates = ApplicationEventEmailTemplate::all();

        return Inertia::render('ApplicationEvents/Index', [
            'events' => $events,
            'templates' => $templates,
        ]);
    }

    public function sync()
    {
        $eventFiles = File::allFiles(app_path('Events'));

        foreach ($eventFiles as $file) {
            $className = 'App\\Events\\' . Str::replaceLast('.php', '', $file->getFilename());
            if (class_exists($className)) {
                ApplicationEvent::firstOrCreate(['name' => $className], ['description' => '']);
            }
        }

        return redirect()->back()->with('success', 'Events synchronized successfully.');
    }

    public function associate(Request $request, ApplicationEvent $event)
    {
        $request->validate([
            'email_template_id' => 'required|exists:email_templates,id',
        ]);

        $event->emailTemplate()->sync([$request->email_template_id]);

        return redirect()->back()->with('success', 'Email template associated successfully.');
    }
}
